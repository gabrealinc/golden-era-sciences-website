const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const source = fs.readFileSync(__dirname + '/google-apps-script/Code.gs', 'utf8');
function run(files, requested) {
  const context = {
    MimeType: {PDF: 'pdf'},
    DriveApp: {
      getFolderById: id => {
        assert(['1Kzf2igXhYnTczGP9-4AJadhxLaXhuo0M', '1prtt04af6J54u5hF5085pekr3me7RP7T'].includes(id));
        const items = files.filter(f => id === (f.name.includes('PURITY') ? '1Kzf2igXhYnTczGP9-4AJadhxLaXhuo0M' : '1prtt04af6J54u5hF5085pekr3me7RP7T'));
        let index = 0;
        return {getFilesByType: () => ({hasNext: () => index < items.length, next: () => {
          const f = items[index++];
          return {getName: () => f.name, getId: () => f.id, getUrl: () => 'https://drive.google.com/file/d/' + f.id + '/view', getDateCreated: () => new Date(f.created), getLastUpdated: () => new Date(f.created)};
        }})};
      },
      getFileById: () => {throw new Error('An unapproved file must never be read.');}
    },
    ContentService: {MimeType: {JSON: 'json'}, createTextOutput: text => ({setMimeType: () => JSON.parse(text)})}
  };
  vm.createContext(context);
  return vm.runInContext(source + '\ndoGet(' + JSON.stringify({parameter: {report: requested}}) + ')', context);
}
const pair = (lot, created) => ['PURITY', 'ENDOTOXIN'].map((type, i) => ({id: lot + i, name: 'BPC-157-10mg__' + lot + '__' + type + '.pdf', created}));
const old = pair('LOT-Z', '2026-01-01');
const fresh = pair('LOT-A', '2026-09-01');
assert.deepEqual(run([...old, ...fresh]).files.map(f => f.id), ['LOT-A0', 'LOT-A1']);
assert.equal(run([...old, fresh[0]]).files.length, 0, 'incomplete new lot retires old pair');
assert.equal(run([...old, ...fresh, fresh[0]]).files.length, 0, 'duplicates fail closed');
assert.equal(run([...old, ...pair('LOT-B', '2026-01-01')]).files.length, 0, 'tied lot uploads fail closed');
assert.equal(run([...old, ...fresh], 'LOT-Z0').error, 'Current report not found.');
assert.equal(run(old, 'arbitrary-private-id').error, 'Current report not found.');
console.log('COA feed tests passed: replacement, incomplete pairs, duplicates, ties, retired and private-file rejection.');
