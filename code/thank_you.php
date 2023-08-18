<!DOCTYPE html>
<html lang="en">
<head>
<title>Thank You | Payment Confirmation</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
}

/* Style the header */
header {
    background-color: #666;
    padding: 30px;
    text-align: center;
    font-size: 35px;
    color: white;
}

/* Create two columns/boxes that floats next to each other */
nav {
    float: left;
    width: 30%;
    height: 300px; /* only for demonstration, should be removed */
    background: #ccc;
    padding: 20px;
}

/* Style the list inside the menu */
nav ul {
    list-style-type: none;
    padding: 0;
}

article {
    float: left;
    padding: 20px;
    width: 100%;
    background-color: #f1f1f1;
    height: 300px; /* only for demonstration, should be removed */
	margin: 0 auto;
    text-align: center;
}

/* Clear floats after the columns */
section:after {
    content: "";
    display: table;
    clear: both;
}

/* Style the footer */
footer {
    background-color: #777;
    padding: 10px;
    text-align: center;
    color: white;
	height:105px;
}

/* Responsive layout - makes the two columns/boxes stack on top of each other instead of next to each other, on small screens */
@media (max-width: 600px) {
    nav, article {
        width: 100%;
        height: auto;
    }
}
.button {
    background-color: #555555; /* Green */
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    margin: 4px 2px;
    cursor: pointer;
}
</style>
</head>
<body>

<header>
  <h2>Payment Confirmation</h2>
</header>

<section>
  <article>
    <h1 style="font-size: 45px;">Thank you for order...!</h1>
    <p style="font-size: 30px;">Your payment is received successfully.</p>
    <!--<p><a href="https://www.michelle.fashion/" class="button">Go to homepage</a></p>-->
  </article>
</section>

<footer>
  <p></p>
</footer>

</body>
</html>
