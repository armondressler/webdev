<html>
<head>
    <link rel="stylesheet" type="text/css" href="additional.css" >
    <script src="editableSelect.js"></script>
</head>
<body>

<form>
    <input type="text" name="myText" value="Norway" selectBoxOptions="Canada;Denmark;Finland;Germany;Mexico;Norway;Sweden;United Kingdom;United States">
	<input type="text" name="myText2" value="" selectBoxOptions="Amy;Andrew;Carol;Jennifer;Jim;Tim;Tommy;Vince">
</form>

<script type="text/javascript">
createEditableSelect(document.forms[0].myText);
createEditableSelect(document.forms[0].myText2);
</script>
</body>
</html>
