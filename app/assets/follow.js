function follow(url_to_fetch){
    var season_id = url_to_fetch.split("=")[1]
    fetch(url_to_fetch)
        .then(response => response.json())
        .then(result => {
            if (!result.success)return
            var ep = document.getElementById(`season-follow-${season_id}`)
            if (ep.classList.contains("nf-fa-bookmark")) {
                ep.classList.remove("nf-fa-bookmark")
                ep.classList.add("nf-fa-bookmark_o")
            }else{
                ep.classList.remove("nf-fa-bookmark_o")
                ep.classList.add("nf-fa-bookmark")
            }
        })
        .catch(error => console.log('error', error));
}