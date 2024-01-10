function follow(url_to_fetch){
    var season_id = url_to_fetch.split("=")[1]
    fetch(url_to_fetch)
        .then(response => response.json())
        .then(result => {
            if (!result.success)return
            var season = document.getElementById(`season-follow-${season_id}`)
            var parent = season.parentElement
            if (season.classList.contains("nf-fa-bookmark")) {
                parent.classList.add("group-hover:translate-y-0")
                parent.classList.remove("translate-y-0")
                season.classList.remove("nf-fa-bookmark")
                season.classList.add("nf-fa-bookmark_o")
            }else{
                parent.classList.remove("group-hover:translate-y-0")
                parent.classList.add("translate-y-0")
                season.classList.remove("nf-fa-bookmark_o")
                season.classList.add("nf-fa-bookmark")
            }
        })
        .catch(error => console.log('error', error));
}