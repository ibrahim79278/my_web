var http = require("http");


http.createServer(function (request, response) {
// Send the HTTP header
5
// HTTP Status: 200 : OK
// Content Type: text/plain
7
response. writeHead(200, {'Content-Type': "text/plain'});


// Send the response body as "Hello World"
response.end("Hello World\n');
}). listen (3000);


// Console will print the message
console.1og('Server running at http://127.0.0.1:3000/');