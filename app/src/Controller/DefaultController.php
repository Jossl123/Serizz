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
        // Getting the four most followed series
        $seriesRepo = $entityManager
            ->getRepository(Series::class);
        $hallOfFameNb = 4;
        $hallOfFameSeries = $seriesRepo->findAllByMostFollowed($hallOfFameNb);

        $usersRepo = $entityManager
            ->getRepository(User::class);
        $ratingRepo = $entityManager
            ->getRepository(Rating::class);
            
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
        /** @var \App\Entity\User */
        $user = $this->getUser();
        $followed_series = $user->getSeries();

        return $this->render('default/index.html.twig', [
            "hall_of_fame" => $hallOfFameSeries,
            "recently_seen" => $followed_series
        ]);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
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

        if (isset($_POST['add'])) {
            $series = new Series();
            $series->setTitle($content['Title']);
            $series->setPoster($posterContent);
            $series->setYearStart(intval($years[0]));
            if (isset($years[1])) {
                $series->setYearEnd(intval($years[1]));
            }

            for ($i = 0; $i < sizeof($genres); $i++) {
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

            if (isset($content['totalSeasons'])) {
                for ($i = 0; $i < $content['totalSeasons']; $i++) {
                    $episodeUrl .= "t=" . $content['Title'] . "&Season=" . ($i + 1);
                    $episodeResponse = $this->client->request('GET', $episodeUrl);
                    $episodeStatusCode = $episodeResponse->getStatusCode();
                    $episodeContentType = $episodeResponse->getHeaders()['content-type'][0];
                    $episodeContent = $episodeResponse->getContent();
                    $episodeContent = $episodeResponse->toArray();

                    $season = new Season();
                    $season->setNumber($i);
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
}
