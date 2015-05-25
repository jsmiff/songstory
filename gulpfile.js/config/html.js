var config = require('./')

module.exports = {
  watch: config.sourceDirectory + '/views/**/*.html',
  src: [config.sourceDirectory + '/views/templates/*.html'],
  dest: config.publicDirectory + '/templates/',
}