const COA_FOLDER_ID = '16yO3ZxYaQoA6iu6qErbXkNaaZKFZhtjz';
const SUBSCRIBER_SHEET_NAME = 'Subscribers';

// Only these verified report folders are read. The parent library is never published.
const COA_REPORT_FOLDERS = [
  '1Kzf2igXhYnTczGP9-4AJadhxLaXhuo0M',
  '1prtt04af6J54u5hF5085pekr3me7RP7T'
];

function doGet(event) {
  const products = {};
  COA_REPORT_FOLDERS.forEach(id => {
    const files = DriveApp.getFolderById(id).getFilesByType(MimeType.PDF);
    while (files.hasNext()) {
      const file = files.next();
      const match = file.getName().match(/^(.+)__([^_]+)__(PURITY|ENDOTOXIN)\.pdf$/i);
      if (!match) continue;
      const sku = match[1].toUpperCase();
      const lot = match[2];
      const type = match[3].toLowerCase();
      const created = file.getDateCreated().getTime();
      if (!products[sku]) products[sku] = {};
      if (!products[sku][lot]) products[sku][lot] = { created: 0, reports: {} };
      const batch = products[sku][lot];
      batch.created = Math.max(batch.created, created);
      // Duplicate files are ambiguous. Do not guess which is approved.
      if (batch.reports[type]) batch.reports[type] = { ambiguous: true };
      else batch.reports[type] = {
        id: file.getId(), name: file.getName(), url: file.getUrl(),
        created: file.getDateCreated().toISOString(),
        updated: file.getLastUpdated().toISOString()
      };
    }
  });
  const output = [];
  Object.keys(products).sort().forEach(sku => {
    const lots = Object.keys(products[sku]).sort((a, b) => products[sku][b].created - products[sku][a].created);
    const batch = products[sku][lots[0]];
    if (lots.length > 1 && batch.created === products[sku][lots[1]].created) return;
    // A new lot immediately retires the old public pair. Publish only a complete pair.
    if (!batch.reports.purity || !batch.reports.endotoxin ||
        batch.reports.purity.ambiguous || batch.reports.endotoxin.ambiguous) return;
    output.push(batch.reports.purity, batch.reports.endotoxin);
  });
  const requested = event && event.parameter && event.parameter.report;
  if (requested) {
    // Public delivery is limited to files already selected in a current complete pair.
    const report = output.find(item => item.id === requested);
    if (!report) return jsonResponse_({ error: 'Current report not found.' });
    const file = DriveApp.getFileById(report.id);
    const thumbnail = file.getThumbnail();
    return jsonResponse_({ id: report.id, name: report.name, updated: report.updated,
      pdf: Utilities.base64Encode(file.getBlob().getBytes()),
      preview: thumbnail ? Utilities.base64Encode(thumbnail.getBytes()) : '' });
  }
  return jsonResponse_({ version: 2, generated: new Date().toISOString(), files: output });
}

function doPost(event) {
  const lock = LockService.getDocumentLock();
  lock.waitLock(10000);

  try {
    const data = JSON.parse(event.postData.contents || '{}');
    const email = String(data.email || '').trim().toLowerCase();
    const phone = String(data.phone_number || '').trim();

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return jsonResponse_({ ok: false, error: 'Invalid email.' });
    }

    const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SUBSCRIBER_SHEET_NAME);
    if (!sheet) {
      return jsonResponse_({ ok: false, error: 'Subscriber sheet not found.' });
    }

    const lastRow = Math.max(sheet.getLastRow(), 2);
    const emails = sheet.getRange(2, 3, lastRow - 1, 1).getDisplayValues();
    let targetRow = -1;

    for (let index = 0; index < emails.length; index += 1) {
      if (String(emails[index][0]).trim().toLowerCase() === email) {
        targetRow = index + 2;
        break;
      }
      if (targetRow === -1 && !String(emails[index][0]).trim()) {
        targetRow = index + 2;
      }
    }

    if (targetRow === -1) {
      targetRow = lastRow + 1;
    }

    const existingEmail = String(sheet.getRange(targetRow, 3).getDisplayValue()).trim();
    if (!existingEmail) {
      sheet.getRange(targetRow, 1, 1, 9).setValues([[
        String(data.first_name || '').trim(),
        String(data.last_name || '').trim(),
        email,
        phone,
        true,
        Boolean(phone),
        '',
        '',
        'Subscriber'
      ]]);
    } else {
      if (data.first_name) sheet.getRange(targetRow, 1).setValue(String(data.first_name).trim());
      if (data.last_name) sheet.getRange(targetRow, 2).setValue(String(data.last_name).trim());
      if (phone) sheet.getRange(targetRow, 4).setValue(phone);
      sheet.getRange(targetRow, 5).setValue(true);
      sheet.getRange(targetRow, 6).setValue(Boolean(phone));
      sheet.getRange(targetRow, 9).setValue('Subscriber');
    }

    return jsonResponse_({ ok: true, duplicate: Boolean(existingEmail) });
  } catch (error) {
    return jsonResponse_({ ok: false, error: String(error && error.message ? error.message : error) });
  } finally {
    lock.releaseLock();
  }
}

function jsonResponse_(payload) {
  return ContentService
    .createTextOutput(JSON.stringify(payload))
    .setMimeType(ContentService.MimeType.JSON);
}
