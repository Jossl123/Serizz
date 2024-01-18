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

class UpdateHelper {
    public static function updateGenre(EntityManagerInterface $entityManager, $content, $s) {
        $genres = $entityManager
            ->getRepository(Genre::class)
            ->findAll();
        $omdbGenres = explode(", ", $content['Genre']);
        $currentGenres = array();
        foreach($omdbGenres as $g) {
            $genre = $entityManager
                ->getRepository(Genre::class)
                ->findBy(['name' => $g]);
            $currentGenres[] = $genre[0];
        }
        foreach($omdbGenres as $g) {
            if (!in_array($g, $currentGenres)) {
                if (in_array($g, $genres)) {
                    $genre = $entityManager
                        ->getRepository(Genre::class)
                        ->findBy(['name' => $g]);
                    $genre[0]->addSeries($s);
                    $s->addGenre($genre);
                } else {
                    $genre = new Genre();
                    $genre->setName($g);
                    $genre->addSeries($s);
                    $s->addGenre($genre);
                    $entityManager->persist($genre);
                }
            }
        }
    }
    
    public static function updateActors(EntityManagerInterface $entityManager, $content, $s) {
        $actors = $entityManager
            ->getRepository(Actor::class)
            ->findAll();
        $omdbActors = explode(", ", $content['Actors']);
        $currentActors = array();
        foreach($omdbActors as $a) {
            $actor = $entityManager
                ->getRepository(Actor::class)
                ->findBy(['name' => $a]);
            $currentActors[] = $actor;
        }
        foreach($omdbActors as $a) {
            if (!in_array($a, $currentActors)) {
                if (in_array($a, $actors)) {
                    $actor = $entityManager
                        ->getRepository(Actor::class)
                        ->findBy(['name' => $a]);
                    $actor[0]->addSeries($s);
                    $s->addActor($actor);
                } else {
                    $actor = new Actor();
                    $actor->setName($a[0]);
                    $actor->addSeries($s);
                    $s->addActor($actor);
                    $entityManager->persist($actor);
                }
            }
        }
    }
    
    public static function updateCountries(EntityManagerInterface $entityManager, $content, $s) {
        $countries = $entityManager
            ->getRepository(Actor::class)
            ->findAll();
        $omdbCountry = $content['Actors'];
        $currentCountry = null;
        if (in_array($omdbCountry, $countries)) {
            $currentCountry = $entityManager
                ->getRepository(Country::class)
                ->findBy(['name' => $omdbCountry]);
            $s->addCountry($currentCountry);
        } else {
            $currentCountry = new Country();
            $currentCountry->setName($omdbCountry);
            $currentCountry->addSeries($s);
            $s->addCountry($currentCountry);
            $entityManager->persist($currentCountry);
        }
    }
    
    public static function updateSeasons(EntityManagerInterface $entityManager, $content, $s) {
        $episodeUrl = "http://www.omdbapi.com/?apikey=3c7a370d&";
        $seasons = $entityManager
            ->getRepository(Season::class)
            ->findBy(['series' => $s]);
        $omdbSeasons = intval($content['totalSeasons']);
        $currentCount = count($seasons);
        if ($currentCount < $omdbSeasons) {
    
            $episodeUrl .= "t=" . $content['Title'];
    
            for ($i = $currentCount; $i < $omdbSeasons; $i++) {
                subSeasonUpdate($entityManager, $i, $currentCount, $episodeUrl);
            }
        }
    }
    
    public static function subSeasonUpdate($entityManager, $i, $currentCount, $episodeUrl, $client) {
        if ($i === $currentCount) {
            $episodeUrl .= "&Season=" . ($i + 1);
        } else {
            $episodeUrl = substr($episodeUrl, 0, -1);
            $episodeUrl .= ($i + 1);
        }
    
        if (strlen($episodeUrl) <= 67) {
            $episodeResponse = $client->request('GET', $episodeUrl);
            $episodeContent = $episodeResponse->toArray();
        }
    
        $season = new Season();
        $season->setNumber($i + 1);
        $season->setSeries($s);
        $s->addSeason($season);
        $entityManager->persist($season);
    
        if (!empty($episodeContent) && array_key_exists('Episodes', $episodeContent)) {
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
                dump('success');
            }
        }
    }
}
