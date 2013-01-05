<?php
defined('is_running') or die('Not an entry point...');
global $page;

?><!DOCTYPE html>
<html>
<head>

<style type="text/css">

body{
	margin:1em 5em;
	font-family: "Lucida Grande",Verdana,"Bitstream Vera Sans",Arial,sans-serif;
	background:#444;
}
div,p,td,th{
	font-size:13px;
	}

a{
	color:#4466aa;
	text-decoration:none;
	}

h1{
	margin-top:0;
	padding-top:0;
	color:#444;
	text-shadow: #888 1px 1px 1px;
	}
h2, h3, h4{
	color:#444;
	text-shadow: #bbb 1px 1px 1px; /* not as dark because they're smaller */
}


.wrapper{
	position:relative;
	width:800px;
	background:#fff;
	margin:0 auto;

	-o-box-shadow: 0px 0px 10px #fff;
	-icab-box-shadow: 0px 0px 10px #fff;
	-khtml-box-shadow: 0px 0px 10px #fff;
	-moz-box-shadow: 0px 0px 10px #fff;
	-webkit-box-shadow: 0px 0px 10px #fff;

	-moz-border-radius: 10px;
	-webkit-border-radius: 10px;
	-o-border-radius: 10px;
	border-radius: 10px;
	padding: 23px;
	border:1px solid #fff;
}

.fullwidth{
	width:100%;
	}
.styledtable{
	border-spacing:0;
	}
.styledtable td, .styledtable th {
	border-bottom: 1px solid #ccc;
	padding: 5px 20px;
	text-align:left;
	vertical-align:top;
	}
.styledtable th{
	background-color:#ededed;
	background-color:#666;
	color:#f1f1f1;
	white-space:nowrap;
	}

.styledtable table td{
	padding:1px;
	border:0 none;
	}


.lang_select{
	position:absolute;
	top:23px;
	right:23px;
}

.lang_select select{
	font-size:130%;
	padding:7px 9px;
}
.lang_select option{
}

.submit{
	font-size:130%;
	padding:7px 9px;
	margin:7px 9px 7px 0;
}


.sm{
	font-size:smaller;
}
input.text{
	width:12em;
}
.failed{
	color:#FF0000;
}
.passed{
	color:#009900;
}
.passed_orange{
	color:orange;
}

.code{
	margin:4px 0;
	padding:5px 7px;
	white-space:nowrap;
	background-color:#f5f5f5;
	}

.inline_message{
	margin:1em 0;
	font-size:19px;
	padding:7px 0;

	border-top: 1px dashed #aaa;
	border-bottom: 1px dashed #aaa;
}
.inline_message .green{
	color:#009900;
	font-weight:normal;
}


.steps li{
	font-weight:bold;
	font-size:13px;
	color:#444;
}
.steps .current{
	color:#009900;
}
.steps .done{
	color:#aaa;
}
.progress li{
	padding: 5px 20px 5px 0;
}

.formtable td, .formtable th{
	padding: 5px 20px 5px 0;
	text-align:left;
	vertical-align:top;
}

</style>

<?php gpOutput::getHead(); ?>

</head>
<body>

<div class="wrapper">

<h1>gpEasy Updater</h1>


<?php $page->GetContent(); ?>

</div>
</body>

</html>

