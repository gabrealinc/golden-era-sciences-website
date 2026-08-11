const COA_FOLDER_ID = '16yO3ZxYaQoA6iu6qErbXkNaaZKFZhtjz';

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
  return ContentService
    .createTextOutput(JSON.stringify({ files: output }))
    .setMimeType(ContentService.MimeType.JSON);
}
