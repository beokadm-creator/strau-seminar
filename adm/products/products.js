$(document).ready(function(){
	
});
function ajaxSelectChange(url,fnm,no,v, nxtno){
	$.ajax({
		url: url+fnm,
		type: "POST",
		dataType:'json',
		data: {
			"no": no
			, "v" : v
		},
		success:function(result){
			let str = "";
			if(!result){
				console.log("NO DATA");
				$("#depth"+nxtno).empty();
				str += "<option value=\"\">DEPTH "+nxtno+"</option>";
				if(nxtno == 2){
					$("#depth3").empty();
					$("#depth3").html("<option value=\"\">DEPTH 3</option>");
				}
			} else {
				str += "<option value=\"\">DEPTH "+nxtno+"</option>";
				for(let i=0; i<result.length; i++){
					let val = result[i];
					str += "<option value=\""+val.cateno+"\" >"+val.catenm+"</option>";
				}
			}
			
			$("#depth"+nxtno).html(str);
		},
		error: function (jqXHR, textStatus, errorThrown) {
			alert("ERROR" + textStatus + " : " + errorThrown);
			self.close();
		}
	});
}


function ajaxDel(cfm,url,fnm,v,uri){
	if(cfm){
		$.ajax({
			url: url+fnm,
			type: "POST",
			dataType:'html',
			data: {"v" : v},
			success:function(result){
				if(!result){
					console.log("실패");
				} else {
					console.log("성공");
				}

				window.location.href=uri;
			},
			error: function (jqXHR, textStatus, errorThrown) {
				alert("ERROR" + textStatus + " : " + errorThrown);
				self.close();
			}
		});
	}
}

function modok(no){
	document.all['cos'+no].src = "../category/category_mod.php?idx="+no;
	document.all['co'+no].style.display = "";
}

function cancel(no){
	window.location.href='/';
	parent.document.all['co'+no].style.display='none';
}

function smartEditorIFrame(id,v){
	nhn.husky.EZCreator.createInIFrame({
		oAppRef : v,
		elPlaceHolder : id,
		sSkinURI : "smarteditor2/SmartEditor2Skin.html",
		fCreator : "createSEditor2"
	});
}

function datepickerHMS(id){
	$(id).datetimepicker({
		dateFormat:'yy-mm-dd',
		monthNamesShort:[ '1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월' ],
		dayNamesMin:[ '일', '월', '화', '수', '목', '금', '토' ],
		changeMonth:true,
		changeYear:true,
		showMonthAfterYear:true,
		// timepicker 설정
		timeFormat:'HH:mm:ss',
		controlType:'select',
		oneLine:true,
	});
}

function minusFile(id){
	$(id).on("click", function(){
		$(this).parent().remove();
		return false;
	});
}


function ajaxSelectChangeDiv(url,fnm,no,v, nxtno){
	$.ajax({
		url: url+fnm,
		type: "POST",
		dataType:'json',
		data: {
			"no": no
			, "v" : v
		},
		success:function(result){
			let str = "";
			if(!result){
				console.log("NO DATA");
				console.log(nxtno);
				$("#depth"+nxtno).empty();
				str += "<option value=\"\">DEPTH "+nxtno+"</option>";
				if(nxtno == 2){
					$("#depth3").empty();
					$("#depth3").html("<option value=\"\">DEPTH 3</option>");
				}
				$("#tag").attr("readonly", false).removeClass("readonly");
				$.ajax({
					url: url+"/products/Ajax.products_tag_update.php",
					type: "POST",
					dataType:'json',
					data: {
						"v" : v
					},
					success:function(resdata){
						let str = "";
						if(!resdata[0].tag){
							console.log("NO DATA11");
							$("#tag").val("");
//							$("#tag").attr("readonly", true).addClass("readonly").val("");
						} else {
							str+= resdata[0].tag;
							$("#w").val("u");
							$("#idx").val(resdata[0].idx);
							$("#tag").val(str);
						}
					},
					error: function (jqXHR, textStatus, errorThrown) {
						alert("ERROR" + textStatus + " : " + errorThrown);
						self.close();
					}
				});
			} else {
				console.log("r : "+nxtno);
				$("#tag").attr("readonly", true).addClass("readonly").val("");
				str += "<option value=\"\">DEPTH "+nxtno+"</option>";
				for(let i=0; i<result.length; i++){
					let val = result[i];
					str += "<option value=\""+val.cateno+"\" >"+val.catenm+"</option>";
				}
			}
			
			$("#depth"+nxtno).html(str);
		},
		error: function (jqXHR, textStatus, errorThrown) {
			alert("ERROR" + textStatus + " : " + errorThrown);
			self.close();
		}
	});
	$("#cateno").val(v);

}