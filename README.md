Charon
======

Is a tool created to retrieve data to your project from database. This is like any other ORM.

With Charon you will:
-------------------------
* Create simple classes using annotations
* Load data with your classes
* Use semantic filters
* Get JSON without NoSQL

Composer:
---
<pre>
require: {
	"evaldobarbosa/charon": "0.1.*@dev"
}
</pre>

Usage:
-------------------------
$conn = new PDO('your_dsn');

$dl = new Charon\Loader( $conn );

Filtering post with your tags and related author
---

<pre>
$dl->load('YourNamespace\Post')
  ->join('tags->tag')
  ->join('author')
  ->equal('post->id',999);
</pre>
  
Choosing output format
---

Using PHP Objects based on classes that you wrote

<pre>
  $rs = $dl->get();
</pre>

Using json

<pre>
  $rs = $dl->get(true);
</pre>

Example
-------
https://github.com/evaldobarbosa/charon-example