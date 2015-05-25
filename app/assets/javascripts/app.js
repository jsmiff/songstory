let $ = require('jquery');
let twig = require('twig').twig;

/*
 * Config
 */
const LOCAL_URL = "http://localhost:3000/spotify/web";

/*
 * Spotify Service
 * Modified code from https://github.com/plamere/playlistminer/
 */
class SpotifyService {

  constructor() {
    this.credentials = {};
  }

  _getTime() {
    return Math.round(new Date().getTime() / 1000)
  }

  _callSpotify(url, data) {
    return $.ajax(url, {
      dataType: 'json',
      data: data,
      headers: {
        'Authorization': 'Bearer ' + this.credentials.token
      }
    });
  }

  checkAuth() {
    return new Promise((resolve, reject) => {
      // if we already have a token and it hasn't expired, use it,
      if ('credentials' in localStorage) {
        this.credentials = JSON.parse(localStorage.credentials);
      }
      if (this.credentials && this.credentials.expires > this._getTime()) {
        resolve();
      } else {
        // we have a token as a hash parameter in the url
        // so parse hash
        var hash = location.hash.replace(/#/g, '');
        var all = hash.split('&');
        var args = {};
        all.forEach(function(keyvalue) {
          var idx = keyvalue.indexOf('=');
          var key = keyvalue.substring(0, idx);
          var val = keyvalue.substring(idx + 1);
          args[key] = val;
        });
        if (typeof(args['access_token']) != 'undefined') {
          var g_access_token = args['access_token'];
          var expiresAt = this._getTime() + 3600;
          if (typeof(args['expires_in']) != 'undefined') {
            var expires = parseInt(args['expires_in']);
            expiresAt = expires + this._getTime();
          }
          this.credentials = {
            token:g_access_token,
            expires:expiresAt
          }
          this._callSpotify('https://api.spotify.com/v1/me').then(
            function(user) {
              this.credentials.user_id = user.id;
              localStorage['credentials'] = JSON.stringify(this.credentials);
              location.hash = '';
              resolve();
            }.bind(this),
            function() {
              reject();
            }
          );
        } else {
          // otherwise, got to spotify to get auth
          reject();
        }
      }
    });
  }

  login() {
    var client_id = '848f01fd5e66406d96e587804fcf9a13';
    var redirect_uri = 'http://songstory.herokuapp.com';
    var scopes = 'playlist-modify-public';
    if (document.location.hostname == 'localhost') {
        redirect_uri = LOCAL_URL;
    }
    var url = 'https://accounts.spotify.com/authorize?client_id=' + client_id +
        '&response_type=token' +
        '&scope=' + encodeURIComponent(scopes) +
        '&redirect_uri=' + encodeURIComponent(redirect_uri);
    document.location = url;
  }

  searchTracks(query) {
    return new Promise((resolve, reject) => {
      this._callSpotify('https://api.spotify.com/v1/search', {
        type: "track",
        q: query
      }).then(function(data) {
        resolve(data.tracks);
      }, function() {
        reject();
      });
    })

  }
}

/*
 * NewEntryApp
 * Create a new entry via logging into Spotify.
 */
class NewEntryApp {

  constructor() {
    this.container = document.getElementById('create-entry-container');
    this.spotifyService = new SpotifyService();
  }

  start() {
    this.spotifyService.checkAuth().then(function() {
      $('#login-form').hide();
      $('#create-entry-form').show();
    }, function() {
      $('#login-form').show();
      $('#create-entry-form').hide();
    });

    this.attachEventHandlers();
  }

  attachEventHandlers() {
    document.getElementById('login-button').addEventListener('click', function(e) {
      this.spotifyService.login();
    }.bind(this));

    document.getElementById('song-input').addEventListener('keyup', function (e) {
      this.spotifyService.searchTracks(e.currentTarget.value).then(function(tracks) {
        console.log(tracks);
      });
    }.bind(this));
  }

  render() {
    $.get('assets/templates/new-entry.html', function(data) {
      var template = twig({
        data: data
      });
      this.container.innerHTML = template.render();
    }.bind(this));
  }

}

/*
 * We are using the server to bootstrap some information on the page. Otherwise
 * when the page loads two client-side modules are started:
 *
 * 1. NewEntryApp: create a new entry via logging into Spotify
 * 2. EntryListApp: view entries created by your and others
 *
 */
$(document).ready(function() {
  var newEntryApp = new NewEntryApp();
  newEntryApp.start();
});
