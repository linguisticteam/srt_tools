function filterStrings(){
	var noChange = $("#showNoChange").is(':checked');
	var changed = $("#showChanged").is(':checked');
	var added = $("#showAdded").is(':checked');
	var deleted = $("#showDeleted").is(':checked');
	var line=0;
	$('#stringList > li').each(function(){
		var str = $(this);
		var cndNoChange = (noChange&&str.hasClass('noChangeString'));
		var cndChanged = (changed&&str.hasClass('changedString'));
		var cndDeleted = (deleted&&str.hasClass('deletedString'));
		var cndAdded = (added&&str.hasClass('addedString'));

		if (cndNoChange||cndChanged||cndDeleted||cndAdded){
			str.removeClass('noDisplay');
			line++;
		}else{
			str.addClass('noDisplay');
		}
	});
	if(line===0&&$('#srtdiff_error').val()==''){
		$('#srtdiff_error').removeClass('noDisplay');
		$('#srtdiff_error').html("There are no strings matching the selected criteria.");	
	}else{
		$('#srtdiff_error').html("");
		$('#srtdiff_error').addClass('noDisplay');
	}
}

function validate(){
	if($("#srtOriginal").val()==''){
		$('#srtform_error').html("<span>Please select an original file to upload</span>");
		$('#srtform_error').addClass('errorShown');
		return false;
	}
	if($("#srtModified").val()==''){
		$('#srtform_error').html("Please select a modified file to upload");
		$('#srtform_error').addClass('errorShown');
		return false;
	}
	
	if($("#srtOriginal").val()!=''&&$("#srtModified").val()!=''){
		if($("#srtOriginal").val().match(/\.srt$/i)===null||$("#srtModified").val().match(/\.srt$/i)===null){
			$('#srtform_error').html("The uploaded files' extensions have to be .srt");
			$('#srtform_error').addClass('errorShown');
			return false;
		}
	}
		
}

jQuery(document).ready(function($) {
	//$(document).tooltip();
	$("#srtOriginal").focus();
	$(window).scroll(function () { 
		$("#srtdiff_controls").hide('slide',{direction:'up'});
	});
		
	$("#srtdiff_controls").hide();
	$("#srtdiff_banner").click(function(){
		var i=0;
		$("#srtdiff_controls").toggle('slide',{direction: (i++%2==0)?"up":"down"});
	});
	$("#showNoChange,#showChanged,#showDeleted,#showAdded").click(filterStrings);
	filterStrings();
});