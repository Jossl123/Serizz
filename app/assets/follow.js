function follow(url_to_fetch){
    fetch(url_to_fetch)
        .then(response => response.json())
        .then(result => {
            if (!result.success)console.log("error")
        })
        .catch(error => console.log('error', error));
}


function mark_as_seen(e, url_to_fetch, first=false) {
    var ep_id = url_to_fetch.split("=")[1]
    var ep = document.getElementById(`ep-${ep_id}`)
    if (!ep.classList.contains("nf-fa-check")){
        e.preventDefault();
        var conf = document.createElement("div")
        var label = document.createElement("button")
        label.innerHTML = "Watch every previous episodes ?"
        var div = document.createElement("div")
        div.classList = ["flex justify-around"]
        var yes = document.createElement("button")
        yes.classList = ["p-1 rounded bg-indigo-400 "]
        yes.textContent = "Yes";
        var no = document.createElement("button")
        no.classList = ["p-1 rounded bg-indigo-400 "]
        no.textContent = "No";
        conf.appendChild(label)
        div.appendChild(yes)
        div.appendChild(no)
        conf.appendChild(div)
        
        conf.classList = ['absolute z-[100] bg-indigo-500 rounded p-1 text-white']
        var scrollX = window.scrollX || window.pageXOffset;
        var scrollY = window.scrollY || window.pageYOffset;
        conf.style.left = (e.clientX  + scrollX) + 'px' ;
        conf.style.top = (e.clientY + scrollY)+ 'px';
        
        document.body.appendChild(conf);



        yes.addEventListener('click', function () {
            watch_ep( url_to_fetch, first, true)
            document.body.removeChild(conf);
        });

        no.addEventListener('click', function () {
            watch_ep( url_to_fetch, first, false)
            conf.style.display = 'none';
            document.body.removeChild(conf);
            
        });

        document.addEventListener('click', function hideConfirmation(e2) {
            if (e != e2 && !conf.contains(e2.target)) {
                conf.style.display = 'none';
                document.body.removeChild(conf);
            }
        });
    }else{
        watch_ep( url_to_fetch, first)
    }
}

function watch_ep( url_to_fetch, first, all=true){
    var see_all = document.getElementById("see_all").children[0]
    if (first && see_all.classList.contains("nf-fa-check"))return
    var ep_id = url_to_fetch.split("=")[1]
    if (!all)url_to_fetch +="&all_prev=false"
    if(first)url_to_fetch+="&all=true"
    console.log(url_to_fetch, first, all)
    fetch(url_to_fetch).then(response => response.json()).then(result => {
        if (!result.success) {
            return
        }
        var progress_bar_serie = document.getElementById("progress_bar_serie");
        var ep = document.getElementById(`ep-${ep_id}`)

        ep.classList.toggle("nf-fa-check")
        ep.classList.toggle("nf-md-eye_off")
        if (ep.classList.contains("nf-fa-check")) {
            if (all){
                var i = 0
                var all_episodes = document.querySelectorAll("#seasons_episodes .nf")
                while (i < all_episodes.length && all_episodes[i] != ep){
                    all_episodes[i].classList.add("nf-fa-check")
                    all_episodes[i].classList.remove("nf-md-eye_off")
                    i++
                };
            }
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
        if (serie_percent == 100){
            see_all.classList.remove("nf-md-eye_off")
            see_all.classList.add("nf-fa-check")
        }else{
            see_all.classList.remove("nf-fa-check") 
            see_all.classList.add("nf-md-eye_off")
        }
    }).catch(error => console.log('error', error));
}