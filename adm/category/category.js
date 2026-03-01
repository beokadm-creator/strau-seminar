

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
	let id = document.querySelectorAll(".coList");
	for(let i=0; i<id.length; i++){
		id[i].style.display = "none";
	}

	document.all['cos'+no].src = "../category/category_mod.php?idx="+no;
	document.all['co'+no].style.display = "";
}

function cancel(no){
	window.location.href='/';
	parent.document.all['co'+no].style.display='none';
}