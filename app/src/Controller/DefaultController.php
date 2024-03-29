<?php

namespace App\Controller;

use App\Entity\Actor;
use App\Entity\Country;
use App\Entity\Episode;
use App\Entity\Genre;
use App\Entity\Rating;
use App\Entity\Season;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Series;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DefaultController extends AbstractController
{
    public function __construct(private HttpClientInterface $client)
    {
    }
    #[Route('/', name: 'app_default')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User */
        $user = $this->getUser();
        if ($user && $user->getBan() == 1) {
            return $this->redirectToRoute('app_banned');
        }
        // Getting the four most followed series
        $seriesRepo = $entityManager
            ->getRepository(Series::class);
        $hallOfFameNb = 4;
        $hallOfFameSeries = $seriesRepo->findAllByMostFollowed($hallOfFameNb);

        $usersRepo = $entityManager
            ->getRepository(User::class);

            
        $search = $entityManager->createQueryBuilder();
        $search->select('COUNT( e.id)')
            ->from('\App\Entity\Episode', 'e')
            ->join('e.user', 'u');  
        $episode_watched = $search->getQuery()->getResult()[0][1];

        if (!$this->isGranted('ROLE_USER')) {
            return $this->render('default/showcase.html.twig', [
                "hall_of_fame" => $hallOfFameSeries,
                "user_nb" => $usersRepo->count([]),
                "watched_episodes" => $episode_watched, 
            ]);
        }
        $followed_series = $user->getSeries();
        $feed=$usersRepo->findAllUpdatesFromFriends($user->getId());

        return $this->render('default/index.html.twig', [
            "hall_of_fame" => $hallOfFameSeries,
            "recently_seen" => $followed_series,
            "feed"=>$feed
        ]);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \ReflectionException
     */
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/add', name:'app_series_add')]
    public function addSeries(Request $request, EntityManagerInterface $entityManager): Response
    {
        $url = "http://www.omdbapi.com/?apikey=3c7a370d&type=series&";
        $posterUrl = "http://img.omdbapi.com/?apikey=3c7a370d&";
        $episodeUrl = "http://www.omdbapi.com/?apikey=3c7a370d&";
        $title = $request->query->get('title', "");
        $year = $request->query->get('year', 2000);
        $url .= "t=" . $title;
        $series = $entityManager
            ->getRepository(Series::class)
            ->findBy(['title' => $title]);

        $content = array();
        $years = array();
        $genres = array();
        $actors = array();
        $country = null;
        $posterContent = null;

        if (isset($_GET['title'])) {
            if (isset($_GET['year'])) {
                $url .= "&y=" . $year;
            }

            $response = $this->client->request('GET', $url);
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaders()['content-type'][0];
            $content = $response->getContent();
            $content = $response->toArray();
            $content["Trailer"] = "";
            if ($content['Response'] != "False") {
                $years = explode("-", $content['Year']);
                $genres = explode(", ", $content['Genre']);
                $actors = explode(", ", $content['Actors']);
                $country = $entityManager
                    ->getRepository(Country::class)
                    ->findBy(['name' => $content['Country']]);

                $posterUrl .= "i=" . $content['imdbID'];
                $posterResponse = $this->client->request('GET', $posterUrl);
                $posterStatusCode = $posterResponse->getStatusCode();
                if ($posterStatusCode == 200) {
                    $posterContentType = $posterResponse->getHeaders()['content-type'][0];
                    $posterContent = $posterResponse->getContent();
                }
                if (isset( $_SERVER['API_YOUTUBE_KEY'])){
                    $API_KEY = $_SERVER['API_YOUTUBE_KEY'];
                    $youtubeTrailerUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&key=".$API_KEY."&type=video&q=".str_replace(' ', '+', $content['Title'])."+trailer";
                    $responsetrailer = $this->client->request('GET', $youtubeTrailerUrl);
                    $statusCodetrailer = $responsetrailer->getStatusCode();
                    if ($statusCodetrailer == 200){
                        $trailer = $responsetrailer->toArray()["items"][0]["id"]["videoId"];
                        $content["Trailer"] = $trailer;
                    }
                }
            }
        }

        if (isset($_POST['update'])) {
            $this->update($content, $content['imdbID'], $entityManager);
        }

        if (isset($_POST['add'])) {
            $series = new Series();
            $series->setTitle($content['Title']);
            $series->setPoster($posterContent);
            $series->setYearStart(intval($years[0]));
            if (isset($years[1])) {
                $series->setYearEnd(intval($years[1]));
            }

            for ($i = 0; $i < sizeof($genres); $i++) {
                $currentsGenres = $entityManager
                    ->getRepository(Genre::class)
                    ->findAll();
                if (in_array($genres[$i], $currentsGenres)) {
                    $genre = $entityManager
                        ->getRepository(Genre::class)
                        ->findBy(['name' => $genres[$i]]);
                    $genre[0]->addSeries($series);
                    $series->addGenre($genre[0]);
                } else {
                    $genre = new Genre();
                    $genre->setName($genres[$i]);
                    $genre->addSeries($series);
                    $series->addGenre($genre);
                    $entityManager->persist($genre);
                }
                $genre = new Genre();
                $genre->setName($genres[$i]);
                $genre->addSeries($series);
                $series->addGenre($genre);
                $entityManager->persist($genre);
            }

            $series->setPlot($content['Plot']);
            $series->setImdb($content['imdbID']);
            $series->setDirector($content['Director']);

            for ($i = 0; $i < sizeof($actors); $i++) {
                $actor = new Actor();
                $actor->setName($actors[$i]);
                $actor->addSeries($series);
                $series->addActor($actor);
                $entityManager->persist($actor);
            }

            if ($country != null) {
                $series->addCountry($country[0]);
            } else {
                $country = new Country();
                $country->setName($content['Country']);
                $country->addSeries($series);
                $series->addCountry($country);
                $entityManager->persist($country);
            }

            $series->setAwards($content['Awards']);
            if ($content["Trailer"] != ""){
                $series->setYoutubeTrailer("https://www.youtube.com/watch?v=".$content["Trailer"]);
            }else{
                $series->setYoutubeTrailer("https://www.youtube.com/watch?v=o9CeEHUG1sU");
            }
            $entityManager->persist($series);

            if (isset($content['totalSeasons']) and $content['totalSeasons'] != "N/A") {
                $episodeUrl .= "t=" . $content['Title'];

                for ($i = 0; $i < $content['totalSeasons']; $i++) {
                    if ($i === 0) {
                        $episodeUrl .= "&Season=" . ($i + 1);
                    } else {
                        $episodeUrl = substr($episodeUrl, 0, -1);
                        $episodeUrl .= ($i + 1);
                    }

                    if (strlen($episodeUrl) <= 67) {
                        $episodeResponse = $this->client->request('GET', $episodeUrl);
                        $episodeContent = $episodeResponse->toArray();
                    }

                    $season = new Season();
                    $season->setNumber($i + 1);
                    $season->setSeries($series);
                    $series->addSeason($season);
                    if (isset($episodeContent['Episodes'])){
                        for ($j = 0; $j < sizeof($episodeContent['Episodes']); $j++) {
                            $episode = new Episode();
                            if (strlen($episodeContent['Episodes'][$j]['Title']) > 128) {
                                $episode->setTitle(substr($episodeContent['Episodes'][$j]['Title'], 0, 128));
                            } else {
                                $episode->setTitle($episodeContent['Episodes'][$j]['Title']);
                            }
                            $episode->setNumber($episodeContent['Episodes'][$j]['Episode']);
                            $episode->setImdb($episodeContent['Episodes'][$j]['imdbID']);
                            $episode->setSeason($season);
                            $season->addEpisode($episode);
                            $entityManager->persist($episode);
                        }
                    }
                    $entityManager->persist($season);
                }
            }

            $entityManager->flush();
        }


        return $this->render('default/add.html.twig', [
            "content" => $content,
            "series" => $series
        ]);
    }

    /**
     * @throws \ReflectionException
     * @throws TransportExceptionInterface
     */
    public function update($content, $id, $entityManager): void
    {
        $valuesToCheckEasy = array(
            "Title",
            "Year",
            "Plot",
            "imdbID",
            "Director",
            "Awards"
        );

        $years = explode("-", $content['Year']);

        $s = $entityManager->getRepository('App\Entity\Series')->findOneBy(['imdb' => $id]);
        foreach ($valuesToCheckEasy as $value) {
            $properties = new \ReflectionClass(Series::class);
            $toCheck = $value;
            if ($value == "Year") {
                $toCheck = "yearStart";
                for ($i = 0; $i < sizeof($years); $i++) {
                    $v = $properties->getProperty($toCheck)->getValue($s);
                    if ($v != intval($years[$i])) {
                        $s->setYearStart(intval($years[0]));
                        if (isset($years[1])) {
                            $s->setYearEnd(intval($years[1]));
                        }
                    }
                    $toCheck = "yearEnd";
                }
            } else if ($value == "imdbID") {
                $toCheck = "imdb";
            }
            if ($value != "Year") {
                $v = $properties->getProperty(strtolower($toCheck))->getValue($s);
                if ($v != $content[$value]) {
                    if ($value == "Title") {
                        $s->setTitle($content[$value]);
                    } else if ($value == "Plot") {
                        $s->setPlot($content[$value]);
                    } else if ($value == "imdbID") {
                        $s->setImdb($content[$value]);
                    } else if ($value == "Director") {
                        $s->setDirector($content[$value]);
                    } else if ($value == "Awards") {
                        $s->setAwards($content[$value]);
                    }
                }
            }
        }

        // update genre
        UpdateHelper::updateGenre($entityManager, $content, $s);

        //update actors
        UpdateHelper::updateActors($entityManager, $content, $s);

        //update countries
        UpdateHelper::updateCountries($entityManager, $content, $s);

        //update seasons
        UpdateHelper::updateSeasons($entityManager, $content, $s, $this->client);

        //flush down all that
        $entityManager->flush();
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/adminpanel', name:'app_admin_panel')]
    public function moderateRatings(Request $request, EntityManagerInterface $entityManager): Response{
        $ratings = $entityManager->getRepository(Rating::class)->createQueryBuilder('r')
        ->where('r.checkrate = 0')
        ->orderBy("r.date")
        ->getQuery()
        ->getResult();

        $series = $entityManager->getRepository(Series::class)->findAll();

        $users = $entityManager->getRepository(User::class)->findAll();
        $limit = 10;
        $page = $request->query->get('page', 1)-1;
        $ratingsNb = count($ratings);
        if ($page > $ratingsNb / $limit) {
            $page = ceil($ratingsNb / $limit);
        }
        if ($page < 0) {
            $page = 0;
        }
        $ratings = array_slice($ratings, $page * $limit, $limit);

        return $this->render('default/adminPanel.html.twig', [
            'ratings' => $ratings,
            'series' => $series,
            'pagesNb' => ceil($ratingsNb / $limit),
            'page' => $page,
            'users' => $users
        ]);
    }


    #[IsGranted("ROLE_ADMIN")]
    #[Route('/adminpanel/{id}', name:'admin_rating_delete')]
    public function deleteRating(Rating $rating, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($rating);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_panel');
    }

    #[IsGranted("ROLE_ADMIN")]
    #[Route('/adminpanel/{id}', name:'admin_rating_approve')]
    public function approveRating(Rating $rating, EntityManagerInterface $entityManager): Response
    {
        $rating->setCheckRate(1);

        $entityManager->persist($rating);
        $entityManager->flush();

        return $this->redirectToRoute('app_admin_panel');
    }

    #[Route('/banned', name:'app_banned')]
    public function banned():Response {
        return $this->render('default/_banned.html.twig');
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \ReflectionException
     */
    #[Route('/{id}/update', name:'app_default_update')]
    public function runUpdate($id, EntityManagerInterface $entityManager): Response {
        $series = $entityManager
            ->getRepository(Series::class)
            ->findOneBy(['id' => $id]);

        $url = "http://www.omdbapi.com/?apikey=3c7a370d&type=series&";
        $url .= "t=" . $series->getTitle();

        $response = $this->client->request('GET', $url);
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $content = $response->toArray();
        $content["Trailer"] = "";

        $this->update($content, $content['imdbID'], $entityManager);

        return $this->redirectToRoute('app_series_show', ['id' => $id]);
    }

}
