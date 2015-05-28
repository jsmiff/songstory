import  $ from 'jquery';

import { NewEntryView } from './modules/NewEntry';

/*
 * We are using the server to bootstrap some information on the page. Otherwise
 * when the page loads client-side modules are started:
 *
 * 1. NewEntryView: create a new entry via logging into Spotify
 *
 */
$(document).ready(function() {
  var newEntryView = new NewEntryView();
  newEntryView.start();
});
