const COA_FOLDER_ID = '16yO3ZxYaQoA6iu6qErbXkNaaZKFZhtjz';
const SUBSCRIBER_SHEET_NAME = 'Subscribers';

function doGet() {
  const folder = DriveApp.getFolderById(COA_FOLDER_ID);
  const files = folder.getFilesByType(MimeType.PDF);
  const output = [];

  while (files.hasNext()) {
    const file = files.next();
    output.push({
      name: file.getName(),
      url: file.getUrl(),
      updated: file.getLastUpdated().toISOString()
    });
  }

  output.sort((a, b) => a.name.localeCompare(b.name));
  return jsonResponse_({ files: output });
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
