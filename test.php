<?php

require_once "determinator.php";

$file = "test.jpg";
$determine = new Determinator($file);
?>
<style>

.text-dominant {
    color:<?php echo $determine->renderHSL($determine->main);?>;
}
.bg-dominant {
    background-color:<?php echo $determine->renderHSL($determine->main);?>;
    display:inline-block;
}
.text-accent {
    color:<?php echo $determine->renderHSL($determine->getMax());?>;
}
.bg-accent {
    background-color:<?php echo $determine->renderHSL($determine->getMax());?>;
}
.text-binary {
    color:<?php echo $determine->renderHSL($determine->binary);?>;
}
.bg-binary {
    background-color:<?php echo $determine->renderHSL($determine->binary);?>;
}
.content {
    padding:20px;
}
.colours {
	margin:10px 0px;
	padding:0px;
	width:410px;
}
.colours li {
	display:inline-block;
	width:31%;
	padding-bottom:30%;
	height:0;
	border:2px solid #333;
}

</style>
<div class="bg-dominant">
	<img src="<?php echo $file;?>" width="400"/>
	<div class="content">
		<h1 class="text-accent">This is a test</h1>
		<h2 class="text-binary">This is a test tagline</h2>
	</div>
</div>
	<ul class="colours">
		<li class="bg-dominant"></li>
		<li class="bg-accent"></li>
		<li class="bg-binary"></li>
	</ul>
<br><br>