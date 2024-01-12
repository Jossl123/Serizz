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


function mark_as_seen(url_to_fetch, season_id) {
    var ep_id = url_to_fetch.split("=")[1]
    fetch(url_to_fetch).then(response => response.json()).then(result => {
        if (!result.success) {
            return
        }
        var progress_bar_serie = document.getElementById("progress_bar_serie");
        var ep = document.getElementById(`ep-${ep_id}`)

        ep.classList.toggle("nf-fa-check")
        ep.classList.toggle("nf-md-eye_off")
        if (ep.classList.contains("nf-fa-check")) {
            var i = 0
            var all_episodes = document.querySelectorAll("#seasons_episodes .nf")
            while (i < all_episodes.length && all_episodes[i] != ep){
                all_episodes[i].classList.add("nf-fa-check")
                all_episodes[i].classList.remove("nf-md-eye_off")
                i++
            };
        }

        document.querySelectorAll('[id^="progress_bar_season_"]').forEach(season => {
            var id_split = season.id.split("_")
            var c_season_id = id_split[id_split.length-1]
            var nb_checked_episodes_season = document.querySelectorAll('#ep-season-'+c_season_id+' .nf-fa-check').length;
            var total_nb_episodes_season = document.querySelectorAll('#ep-season-'+c_season_id+' .nf').length;
            var season_percent = parseInt(nb_checked_episodes_season/total_nb_episodes_season*100);
            for (let i = season.classList.length - 1; i >= 0; i--) {
                const className = season.classList[i];
                if (className.startsWith('w-')) {
                    season.classList.remove(className);
                }
                season.classList.add("w-["+season_percent+"%]")
            }
        });

        var nb_checked_episodes = document.querySelectorAll('#seasons_episodes .nf-fa-check').length;
        var total_nb_episodes = document.querySelectorAll('#seasons_episodes .nf-md-eye_off').length + nb_checked_episodes;
        var serie_percent = parseInt(nb_checked_episodes/total_nb_episodes*100)
        for (let i = progress_bar_serie.classList.length - 1; i >= 0; i--) {
            const className = progress_bar_serie.classList[i];
            if (className.startsWith('w-')) {
                progress_bar_serie.classList.remove(className);
            }
            progress_bar_serie.classList.add("w-["+serie_percent+"%]")
        }


    }).catch(error => console.log('error', error));
}