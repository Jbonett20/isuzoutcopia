$(function(){
    dealerid = 0;
    monthid = 0;
    $("#prodmonthhome").select2({});
    $("#dealerhome").select2({
        templateResult: formatState,
        templateSelection: formatSelection
    });
    function formatState (state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = "../admin/img/country";
        var $state = $(
            '<span><img src="' + baseUrl + '/' + state.title + '" style="width:20px;" /> ' + state.text + '</span>'
        );
        return $state;
    };
    function formatSelection (state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = "../admin/img/country";
        var $state = $(
            '<span><img src="' + baseUrl + '/' + state.title + '" style="width:20px;" /> ' + state.text + '</span>'
        );
        // Use .text() instead of HTML string concatenation to avoid script injection issues
        $state.find("span").text(state.text);
        $state.find("img").attr("src", baseUrl + "/" + state.title);
        return $state;
    };
    $('#dealerhome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarHome();
    });
    $('#prodmonthhome').on('select2:select', function (e) {
        var data = e.params.data;
        CargarHome();
    });
    function CargarHome(){
        dealerid = $('#dealerhome').val(); 
        monthid = $('#prodmonthhome').val();
        $('#OrdersList').load('orderlist.php?dealerid='+dealerid+'&monthid='+monthid);
        console.log("Dealer: " + dealerid + " | Mes: " + monthid);
    }
    CargarHome();
});