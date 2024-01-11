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
        var ep = document.getElementById (`ep-${ep_id}`)
        if (ep.classList.contains("nf-md-eye_off")) {
            ep.classList.remove("nf-md-eye_off")
            ep.classList.add("nf-fa-check")
        } else {
            ep.classList.remove("nf-fa-check")
            ep.classList.add("nf-md-eye_off")
        }

        var progress_bar_serie = document.getElementById("progress_bar_serie");
        var nb_checked_episodes = document.querySelectorAll('.nf-fa-check').length;
        var total_nb_episodes = document.querySelectorAll('.nf').length;
        var serie_percent = parseInt(nb_checked_episodes/total_nb_episodes*100)
        for (let i = progress_bar_serie.classList.length - 1; i >= 0; i--) {
            const className = progress_bar_serie.classList[i];
            if (className.startsWith('w-')) {
                progress_bar_serie.classList.remove(className);
            }
            progress_bar_serie.classList.add("w-["+serie_percent+"%]")
        }

        var progress_bar_season = document.getElementById("progress_bar_season_"+season_id);
        var nb_checked_episodes_season = document.querySelectorAll('#ep-season-'+season_id+' .nf-fa-check').length;
        var total_nb_episodes_season = document.querySelectorAll('#ep-season-'+season_id+' .nf').length;
        var season_percent = parseInt(nb_checked_episodes_season/total_nb_episodes_season*100)
        for (let i = progress_bar_season.classList.length - 1; i >= 0; i--) {
            const className = progress_bar_season.classList[i];
            if (className.startsWith('w-')) {
                progress_bar_season.classList.remove(className);
            }
            progress_bar_season.classList.add("w-["+season_percent+"%]")
        }

    }).catch(error => console.log('error', error));
}