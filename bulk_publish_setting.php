<?php
function bulk_post_publish_setting(){
$max_time = (int)(ini_get('max_execution_time'));
$max_upload = (int)(ini_get('upload_max_filesize'));
$max_post = (int)(ini_get('post_max_size'));
$memory_limit = (int)(ini_get('memory_limit'));
echo $max_time;
echo "</br>";
echo $max_upload;
echo "</br>";
echo $max_post;
echo "</br>";
echo $memory_limit;
echo "</br>";
	?>
    <link type="text/css" href="<?php echo plugins_url()?>/Post-Doc/css/style.css" rel="stylesheet" />
    <div class="form-style-6" style="width:50%;padding-top:50px;">
		<h4>Execute file link :</h4>
    </div>
	<input id="foo" type="text" value="<?php echo plugins_url()?>/Post-Doc/bulk_publish.php?folder_path=ENTER DROPBOX FOLDER PATH" class="copyclickboard" style="width:70%;">
    <button class="btn clipboardbtn" data-clipboard-action="copy" data-clipboard-target="#foo" >Copy</button>
	
	<form action="" method="POST" NAME="I_F">
		<input class="copyclickboard" size="100" value="<?php echo plugins_url()?>/Post-Doc/bulk_publish.php?folder_path=ENTER DROPBOX FOLDER PATH" name="scriptpath">
		<button class="btn clipboardbtn" onclick="testpath()">Upload Now</button>	   	   
	</form>
	
	<script language="JavaScript">
		function testpath()
		{
			if (confirm("If you have entered the path to the script correctly the script will be executed NOW.\n\nDo you wish to continue?")) 
				{
					var tWindow = null;
					if(tWindow != null) if(!tWindow.closed) tWindow.close()
					tWindow=open(document.I_F.scriptpath.value,'tWin'); 
				}
		}
	</script>
	<script language="JavaScript">
		original_minutes=0;
		original_hours=0;
		original_days=0;
		original_weeks=0;

		function disable_others()
		{
			validcount=0;
			with (document.I_F)
			{
				if (minutes.options[minutes.selectedIndex].value<0) validcount=validcount+minutes.options[minutes.selectedIndex].value *1; 
				if (hours.options[hours.selectedIndex].value<0) validcount=validcount+hours.options[hours.selectedIndex].value *1; 
				if (days.options[days.selectedIndex].value<0) validcount=validcount+days.options[days.selectedIndex].value *1;
				if (weeks.options[weeks.selectedIndex].value<0) validcount=validcount+weeks.options[weeks.selectedIndex].value *1; 
				if (validcount!=-3) 
					{
						minutes.value=-1;
						minutes.value=-1;
						hours.value=-1;
						days.value=-1;
						weeks.value=-1;
						alert("Only one can carry a value!  Please re-select.");
					}
			}
		}
		function add()
		{
			with (document.I_F)
				{
					if ( (minutes.options[minutes.selectedIndex].value<0) & (hours.options[hours.selectedIndex].value<0) &(days.options[days.selectedIndex].value<0) & (weeks.options[weeks.selectedIndex].value<0) ) alert("Please select a period to run this script:\n\n MINUTES, HOURLY, DAILY or WEEKLY");
					else submit();
				}
		}
	</script>	
	<script src="<?php echo plugins_url()?>/Post-Doc/js/clipboard.min.js"></script>
	<script>
		var clipboard = new Clipboard('.btn');
		clipboard.on('success', function(e) {
			console.log(e);
		});
		clipboard.on('error', function(e) {
			console.log(e);
		});
	</script>
<?php
}