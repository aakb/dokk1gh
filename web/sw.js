var CACHE_NAME = 'code2dokk1-v1';
var urlsToCache = [
//   '/stylesheets/app.css',
//   '/static/js/jquery.min.js',
//   '/static/js/popper.min.js',
//   '/static/js/bootstrap.min.js'
];

self.addEventListener("install", function(event) {
    // Perform install steps
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            console.log("Opened cache");
            console.log(urlsToCache);
            return cache.addAll(urlsToCache);
        })
    );
});
