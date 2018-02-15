//iGenius Main Function
$(document).ready(function() {
	sideBar();
});	

function sideBar(){
	$.post("./model/sidebar.html", function (data){
		$(".sideBar").html(data);
		init_sidebar();
	})
}