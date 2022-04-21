# Test

This is a code test.  A very simple REST interface to filter the SF food truck data available through their public API.

### To run this code locally:

* Clone or download the repo.
* There is just one file in the public directory (your server needs to have that as the document root).
* composer update to pull the php Guzzle client into the vendor directory.
* After that you should be able to hit the root directory or index.php if you really want to get picky.

### Filtering data:

There are four available filters:
- my_location: address where you are located
- distance: radius to search for a food truck
- type: cart or truck
- fooditems: currently only one item per request (e.g. chicken)

If you send a location you must also send a distance (in miles) and it can be a decimal such as 1.5 or .5 or 1 etc.

You can send requests via GET or POST

An example address for testing is: 536 14th Street, San Francisco, CA 94103

So an example GET url would look something like this:

    ?my_location=536%2014th%20Street,%20San%20Francisco,%20CA%2094103&distance=.5&type=truck&fooditems=chicken
