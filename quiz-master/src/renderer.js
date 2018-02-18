// This file is required by the index.html file and will
// be executed in the renderer process for that window.
// All of the Node.js APIs are available in this process.
// 043616abbd5c8bd44033bf4646d30e98af2d9e9a
const BrowserWindow = require('electron').remote.BrowserWindow
const path = require('path')

var db = new PouchDB('dbname')

function reinit () {
  CURRENT_URL = window.location.href.split('#')[0].split('?')[0]
  $BODY = $('body')
  $MENU_TOGGLE = $('#menu_toggle')
  $SIDEBAR_MENU = $('#sidebar-menu')
  $SIDEBAR_FOOTER = $('.sidebar-footer')
  $LEFT_COL = $('.left_col')
  $RIGHT_COL = $('.right_col')
  $NAV_MENU = $('.nav_menu')
  $FOOTER = $('footer')
}

$(document).ready(function () {
  $.post('../components/sidebar.html', function (data) {
    $('.sidebar').html(data)
    reinit()
  })

  $.post('../components/topnav.html', function (data) {
    $('.top_nav').html(data)
    reinit()
    init_sidebar()
  })
})

window.addEventListener('keypress', shortcuts, true)

function shortcuts (data) {
  if (data.code == 'Digit1') {
    const modalPath = path.join('file://', __dirname, 'index.html')
    let win = new BrowserWindow({ width: 400, height: 320 })
    win.on('close', function () { win = null })
    win.loadURL(modalPath)
    win.show()
  }
}

$('#add-admin-form').submit(function () {
  db.get('admins').then(function (doc1) {
    console.log(doc1.admin)
    doc1.admin.push({
      name: $('#name').val(),
      password: $('#password').val()
    })
    return db.put({
      _id: 'admins',
      _rev: doc1._rev,
      admin: doc1.admin
    })
  }).then(function (response) {
    console.log(response)
  }).catch(function (err) {
    if (err.status == 404) {
      var doc = {
        _id: 'admins',
        admin: [{
          name: $('#name').val(),
          password: $('#password').val()
        }]
      }
      db.put(doc)
    }
  })
  alert('Added a new Admin Account to PouchDB')
  event.preventDefault()
})
