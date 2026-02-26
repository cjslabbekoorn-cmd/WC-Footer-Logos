window.addEventListener('load', function () {
  var imgs = document.querySelectorAll('.wcfl__img--lazy[data-src]');
  imgs.forEach(function (img) {
    var src = img.getAttribute('data-src');
    if (src) {
      img.setAttribute('src', src);
      img.removeAttribute('data-src');
    }
  });
});
