
function show_season(season){
    var seasons = document.querySelectorAll(".season");
    for (let i = 0; i < seasons.length; i++){
        document.getElementById(`ep-season-${i+1}`).style.display = "none";
        document.getElementById(`season-${i+1}`).classList.remove("current");
    }
    document.getElementById("ep-season-" + season).style.display = "block";
    document.getElementById("season-" + season).classList.add("current");
}
show_season(1)