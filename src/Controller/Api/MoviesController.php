<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;
/* aggiungo l'ordinamento direttamente in chiamta api in base alla data di uscita e al rating  */

class MoviesController extends AbstractController
{
    #[Route('/api/movies')]
    public function list(Connection $db): Response
    {

        $rows = $db->createQueryBuilder()
            ->select('m.*, GROUP_CONCAT(g.id SEPARATOR ", ") AS genres')
            ->from('movies', 'm')
            ->leftJoin('m', 'movies_genres', 'mg', 'm.id = mg.movie_id') // Join sinistra tra la tabella "movies" e "movies_genres"
            ->leftJoin('mg', 'genres', 'g', 'mg.genre_id = g.id') // Join sinistra tra la tabella "movies_genres" e "genres"
            ->orderBy('m.release_date', 'DESC') // Ordina per data di uscita 
            ->addOrderBy('CASE WHEN m.rating IS NULL THEN 1 ELSE 0 END') // Ordina per rating 
            ->addOrderBy('m.rating', 'DESC') // Ordina per rating (in caso di rating uguale, dal più alto al più basso)
            ->groupBy('m.id') 
            ->setMaxResults(50) 
            ->executeQuery() 
            ->fetchAllAssociative(); // Restituisce un array associativo con tutti i risultati della query
        
        return $this->json([
            "movies" => $rows
        ]);
    }
    #[Route('/api/movies/genre/{genreName}')]
    public function listByGenre(Connection $db, string $genreName): Response
    {
       /*  in questa funzione a differenza della precedente vine passato il nome della categoria {genreName} per effettuare con la stessa logica una chiamata contenente solo i film che contengono quel genere  */
        $rows = $db->createQueryBuilder()
            ->select('m.*, GROUP_CONCAT(g.id SEPARATOR ", ") AS genres')
            ->from('movies', 'm')
            ->leftJoin('m', 'movies_genres', 'mg', 'm.id = mg.movie_id')
            ->leftJoin('mg', 'genres', 'g', 'mg.genre_id = g.id')
            ->where('LOWER(g.id) LIKE :genreName')
            ->setParameter('genreName', '%' . strtolower($genreName) . '%')
            ->orderBy('m.release_date', 'DESC')
            ->addOrderBy('CASE WHEN m.rating IS NULL THEN 1 ELSE 0 END')
            ->addOrderBy('m.rating', 'DESC')
            ->groupBy('m.id')
            ->setMaxResults(50)
            ->executeQuery()
            ->fetchAllAssociative();

        return $this->json([
            "movies" => $rows
        ]);
    }
}
