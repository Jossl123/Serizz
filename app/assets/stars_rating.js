function rate_star_click(left,i){
    if(left){
        rate_star_click_right_hide(i)
        rate_star_click_left_show(i)
    }else{
        if (i<4)rate_star_click_left_hide(i+1)
        rate_star_click_right_show(i)
    }
}

function rate_star_click_left_show(i){
    document.getElementById(`rate-star-yellow-left-${i}`).style.display="block"
    if (i > 0)rate_star_click_right_show(i-1)
}

function rate_star_click_right_show(i){
    document.getElementById(`rate-star-yellow-right-${i}`).style.display="block"
    rate_star_click_left_show(i)
}

function rate_star_click_left_hide(i){
    document.getElementById(`rate-star-yellow-left-${i}`).style.display="none"
    rate_star_click_right_hide(i)
}

function rate_star_click_right_hide(i){
    document.getElementById(`rate-star-yellow-right-${i}`).style.display="none"
    if (i < 4)rate_star_click_left_hide(i+1)
}