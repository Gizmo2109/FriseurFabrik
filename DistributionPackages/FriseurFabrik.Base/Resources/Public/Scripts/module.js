// Backend modules: confirmation dialogs for destructive actions
document.addEventListener('submit', function (event) {
  var form = event.target;
  if (form.hasAttribute && form.hasAttribute('data-confirm') && !window.confirm(form.getAttribute('data-confirm'))) {
    event.preventDefault();
  }
}, true);
