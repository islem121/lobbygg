<?php

declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TournamentManagementController extends AbstractController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Route('/tournaments/frontoffice', name: 'tournament_frontoffice', methods: ['GET'])]
    public function frontoffice(Request $request): Response
    {
        $session = $request->getSession();
        $queryUserId = filter_var($request->query->get('user_id'), FILTER_VALIDATE_INT);
        if ($queryUserId && $queryUserId > 0) {
            $session->set('manual_user_id', (int) $queryUserId);
        }

        $userId = $this->resolveUserId($request);
        
        // Get filter, sort, and search parameters
        $filters = [
            'search' => trim((string) $request->query->get('search', '')),
            'status' => $request->query->get('status', ''),
            'region' => $request->query->get('region', ''),
            'mode' => $request->query->get('mode', ''),
        ];
        $sort = $request->query->get('sort', 'start_datetime');
        $order = $request->query->get('order', 'asc');
        
        $tournaments = $this->fetchTournamentsWithRegistration($userId, $filters, $sort, $order);
        
        // Get unique values for filter dropdowns
        $filterOptions = $this->getFilterOptions();

        return $this->render('tournaments/frontoffice.html.twig', [
            'page' => 'tournaments',
            'userId' => $userId,
            'tournaments' => $tournaments,
            'filters' => $filters,
            'sort' => $sort,
            'order' => $order,
            'filterOptions' => $filterOptions,
        ]);
    }

    #[Route('/admin/tournaments', name: 'tournament_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        // Get filter, sort, and search parameters
        $filters = [
            'search' => trim((string) $request->query->get('search', '')),
            'status' => $request->query->get('status', ''),
            'region' => $request->query->get('region', ''),
            'mode' => $request->query->get('mode', ''),
        ];
        $sort = $request->query->get('sort', 'created_at');
        $order = $request->query->get('order', 'desc');
        
        $tournaments = $this->fetchTournamentsForDashboard($filters, $sort, $order);
        
        // Get unique values for filter dropdowns
        $filterOptions = $this->getFilterOptions();

        return $this->render('tournaments/dashboard.html.twig', [
            'tournaments' => $tournaments,
            'filters' => $filters,
            'sort' => $sort,
            'order' => $order,
            'filterOptions' => $filterOptions,
        ]);
    }

    #[Route('/tournaments/api/list', name: 'list_tournaments_api', methods: ['GET'])]
    #[Route('/tournaments/list_tournaments.php', name: 'list_tournaments_php_alias', methods: ['GET'])]
    public function listTournaments(Request $request): JsonResponse
    {
        try {
            $userId = filter_var($request->query->get('user_id'), FILTER_VALIDATE_INT);
            
            // Get filter, sort, and search parameters
            $filters = [
                'search' => trim((string) $request->query->get('search', '')),
                'status' => $request->query->get('status', ''),
                'region' => $request->query->get('region', ''),
                'mode' => $request->query->get('mode', ''),
            ];
            $sort = $request->query->get('sort', 'start_datetime');
            $order = $request->query->get('order', 'asc');
            
            $tournaments = $this->fetchTournamentsWithRegistration($userId ?: null, $filters, $sort, $order);
            return $this->jsonSuccess('Tournaments loaded successfully.', ['tournaments' => $tournaments]);
        } catch (\Throwable $e) {
            return $this->jsonError('Failed to load tournaments: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/tournaments/api/get', name: 'get_tournament_api', methods: ['GET'])]
    #[Route('/tournaments/get_tournament.php', name: 'get_tournament_php_alias', methods: ['GET'])]
    public function getTournament(Request $request): JsonResponse
    {
        $id = filter_var($request->query->get('id'), FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            return $this->jsonError('Invalid tournament id.', 400);
        }

        try {
            $sql = <<<SQL
SELECT
    t.*,
    COUNT(tp.participation_id) AS registered_teams
FROM tournament t
LEFT JOIN tournament_participation tp
    ON tp.tournament_id = t.id
    AND tp.status = 'registered'
WHERE t.id = :id
GROUP BY t.id
SQL;
            $tournament = $this->connection->fetchAssociative($sql, ['id' => $id]);
            if (!$tournament) {
                return $this->jsonError('Tournament not found.', 404);
            }

            $userId = filter_var($request->query->get('user_id'), FILTER_VALIDATE_INT);
            if ($userId && $userId > 0) {
                $tournament['is_registered'] = $this->isRegistered((int) $id, (int) $userId);
            }

            return $this->jsonSuccess('Tournament loaded successfully.', ['tournament' => $tournament]);
        } catch (\Throwable $e) {
            return $this->jsonError('Failed to fetch tournament: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/tournaments/api/create', name: 'create_tournament_api', methods: ['POST'])]
    #[Route('/tournaments/create_tournament.php', name: 'create_tournament_php_alias', methods: ['POST'])]
    public function createTournament(Request $request): JsonResponse
    {
        [$clean, $errors] = $this->validateTournamentPayload($this->readInput($request), false);
        if ($errors) {
            return $this->jsonError('Validation failed.', 422, ['errors' => $errors]);
        }

        try {
            $this->connection->insert('tournament', [
                'name' => $clean['name'],
                'status' => $clean['status'],
                'start_datetime' => $clean['start_datetime'],
                'end_datetime' => $clean['end_datetime'],
                'region' => $clean['region'],
                'mode' => $clean['mode'],
                'max_teams' => $clean['max_teams'],
                'description' => $clean['description'],
                'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            return $this->jsonSuccess('Tournament created successfully.', [
                'id' => (int) $this->connection->lastInsertId(),
            ], 201);
        } catch (\Throwable $e) {
            return $this->jsonError('Failed to create tournament: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/tournaments/api/update', name: 'update_tournament_api', methods: ['POST', 'PUT'])]
    #[Route('/tournaments/update_tournament.php', name: 'update_tournament_php_alias', methods: ['POST', 'PUT'])]
    public function updateTournament(Request $request): JsonResponse
    {
        [$clean, $errors] = $this->validateTournamentPayload($this->readInput($request), true);
        if ($errors) {
            return $this->jsonError('Validation failed.', 422, ['errors' => $errors]);
        }

        try {
            $registered = $this->registeredCount($clean['id']);
            if ($clean['max_teams'] < $registered) {
                return $this->jsonError(
                    sprintf('max_teams cannot be lower than registered teams (%d).', $registered),
                    409
                );
            }

            $affected = $this->connection->update('tournament', [
                'name' => $clean['name'],
                'status' => $clean['status'],
                'start_datetime' => $clean['start_datetime'],
                'end_datetime' => $clean['end_datetime'],
                'region' => $clean['region'],
                'mode' => $clean['mode'],
                'max_teams' => $clean['max_teams'],
                'description' => $clean['description'],
            ], ['id' => $clean['id']]);

            if ($affected === 0) {
                return $this->jsonError('Tournament not found or no changes applied.', 404);
            }

            return $this->jsonSuccess('Tournament updated successfully.');
        } catch (\Throwable $e) {
            return $this->jsonError('Failed to update tournament: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/tournaments/api/delete', name: 'delete_tournament_api', methods: ['POST'])]
    #[Route('/tournaments/delete_tournament.php', name: 'delete_tournament_php_alias', methods: ['POST'])]
    public function deleteTournament(Request $request): JsonResponse
    {
        $input = $this->readInput($request);
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            return $this->jsonError('Invalid tournament id.', 422);
        }

        $this->connection->beginTransaction();
        try {
            $this->connection->delete('tournament_participation', ['tournament_id' => $id]);
            $deleted = $this->connection->delete('tournament', ['id' => $id]);
            if ($deleted === 0) {
                $this->connection->rollBack();
                return $this->jsonError('Tournament not found.', 404);
            }

            $this->connection->commit();
            return $this->jsonSuccess('Tournament deleted successfully.');
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            return $this->jsonError('Failed to delete tournament: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/tournaments/api/register', name: 'register_tournament_api', methods: ['POST'])]
    #[Route('/tournaments/register_tournament.php', name: 'register_tournament_php_alias', methods: ['POST'])]
    public function registerTournament(Request $request): JsonResponse
    {
        $input = $this->readInput($request);
        $tournamentId = filter_var($input['tournament_id'] ?? null, FILTER_VALIDATE_INT);
        $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId || $userId < 1) {
            $userId = $this->resolveUserId($request);
        }

        if (!$tournamentId || $tournamentId < 1) {
            return $this->jsonError('Invalid tournament id.', 422);
        }
        if (!$userId || $userId < 1) {
            return $this->jsonError('Valid user_id is required to register.', 422);
        }
        if (!$this->participationUserExists((int) $userId)) {
            return $this->jsonError('Invalid user_id. It must exist in users.user_id.', 422);
        }

        $this->connection->beginTransaction();
        try {
            $tournament = $this->connection->fetchAssociative(
                'SELECT id, max_teams FROM tournament WHERE id = :id FOR UPDATE',
                ['id' => $tournamentId]
            );
            if (!$tournament) {
                $this->connection->rollBack();
                return $this->jsonError('Tournament not found.', 404);
            }

            if ($this->isRegistered((int) $tournamentId, (int) $userId)) {
                $this->connection->rollBack();
                return $this->jsonError('User is already registered in this tournament.', 409);
            }

            $registeredCount = $this->registeredCount((int) $tournamentId);
            if ($registeredCount >= (int) $tournament['max_teams']) {
                $this->connection->rollBack();
                return $this->jsonError('Tournament is full. Registration closed.', 409);
            }

            $this->connection->insert('tournament_participation', [
                'user_id' => $userId,
                'tournament_id' => $tournamentId,
                'status' => 'registered',
                'joined_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            $this->connection->commit();
            return $this->jsonSuccess('Registration successful.', [
                'participation_id' => (int) $this->connection->lastInsertId(),
                'registered_teams' => $registeredCount + 1,
                'max_teams' => (int) $tournament['max_teams'],
            ]);
        } catch (\Throwable $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }
            if (str_contains($e->getMessage(), 'FOREIGN KEY') && str_contains($e->getMessage(), 'users')) {
                return $this->jsonError('Invalid user_id. It must exist in users.user_id.', 422);
            }
            return $this->jsonError('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    private function readInput(Request $request): array
    {
        $contentType = (string) $request->headers->get('Content-Type', '');
        if (str_contains(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($request->getContent(), true);
            return is_array($decoded) ? $decoded : [];
        }

        return $request->request->all();
    }

    private function resolveUserId(Request $request): ?int
    {
        $queryUserId = filter_var($request->query->get('user_id'), FILTER_VALIDATE_INT);
        if ($queryUserId && $queryUserId > 0) {
            return (int) $queryUserId;
        }

        $sessionUserId = $request->getSession()->get('manual_user_id');
        if (is_int($sessionUserId) && $sessionUserId > 0) {
            return $sessionUserId;
        }

        return null;
    }

    private function participationUserExists(int $userId): bool
    {
        $hasUsersTable = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name',
            ['table_name' => 'users']
        );

        if ($hasUsersTable > 0) {
            return (bool) $this->connection->fetchOne(
                'SELECT 1 FROM users WHERE user_id = :user_id LIMIT 1',
                ['user_id' => $userId]
            );
        }

        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM user WHERE id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );
    }

    private function fetchTournamentsWithRegistration(?int $userId, array $filters = [], string $sort = 'start_datetime', string $order = 'asc'): array
    {
        $sql = "SELECT
            t.id,
            t.name,
            t.status,
            t.start_datetime,
            t.end_datetime,
            t.region,
            t.mode,
            t.max_teams,
            t.description,
            t.created_at,
            COUNT(tp.participation_id) AS registered_teams
        FROM tournament t
        LEFT JOIN tournament_participation tp
            ON tp.tournament_id = t.id
            AND tp.status = 'registered'";
        
        $where = [];
        $params = [];
        
        // Add search filter
        if (!empty($filters['search'])) {
            $where[] = "(t.name LIKE :search OR t.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Add status filter
        if (!empty($filters['status'])) {
            $where[] = "t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Add region filter
        if (!empty($filters['region'])) {
            $where[] = "t.region = :region";
            $params['region'] = $filters['region'];
        }
        
        // Add mode filter
        if (!empty($filters['mode'])) {
            $where[] = "t.mode = :mode";
            $params['mode'] = $filters['mode'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY t.id";
        
        // Add sorting
        $allowedSorts = ['start_datetime', 'name', 'status', 'created_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'start_datetime';
        $order = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY t.$sort $order, t.id DESC";
        
        $rows = $this->connection->fetchAllAssociative($sql, $params);

        if (!$userId || $userId < 1) {
            foreach ($rows as &$row) {
                $row['is_registered'] = false;
            }
            return $rows;
        }

        $registeredRows = $this->connection->fetchFirstColumn(
            'SELECT tournament_id FROM tournament_participation WHERE user_id = :user_id AND status = :status',
            ['user_id' => $userId, 'status' => 'registered']
        );
        $lookup = [];
        foreach ($registeredRows as $registeredId) {
            $lookup[(int) $registeredId] = true;
        }

        foreach ($rows as &$row) {
            $row['is_registered'] = isset($lookup[(int) $row['id']]);
        }

        return $rows;
    }

    private function validateTournamentPayload(array $data, bool $isUpdate = false): array
{
    $errors = [];
    $clean = [];

    // ID (required for update)
    if ($isUpdate) {
        if (empty($data['id']) || !ctype_digit((string)$data['id'])) {
            $errors['id'] = 'Valid tournament ID is required.';
        } else {
            $clean['id'] = (int) $data['id'];
        }
    }

    // Name
    if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
        $errors['name'] = 'Name must be at least 3 characters.';
    } else {
        $clean['name'] = htmlspecialchars(trim($data['name']));
    }

    // Status (allowed values)
    $allowedStatuses = ['open', 'closed', 'ongoing', 'completed'];
    if (empty($data['status']) || !in_array($data['status'], $allowedStatuses)) {
        $errors['status'] = 'Invalid status selected.';
    } else {
        $clean['status'] = $data['status'];
    }

    // Dates
    if (empty($data['start_datetime']) || !$this->isValidDate($data['start_datetime'])) {
        $errors['start_datetime'] = 'Invalid start date.';
    } else {
        $clean['start_datetime'] = $data['start_datetime'];
    }

    if (empty($data['end_datetime']) || !$this->isValidDate($data['end_datetime'])) {
        $errors['end_datetime'] = 'Invalid end date.';
    } else {
        $clean['end_datetime'] = $data['end_datetime'];
    }

    if (!empty($clean['start_datetime']) && !empty($clean['end_datetime'])) {
        if (strtotime($clean['end_datetime']) <= strtotime($clean['start_datetime'])) {
            $errors['end_datetime'] = 'End date must be after start date.';
        }
    }

    // Region
    if (empty($data['region']) || strlen(trim($data['region'])) < 2) {
        $errors['region'] = 'Region is required.';
    } else {
        $clean['region'] = htmlspecialchars(trim($data['region']));
    }

    // Mode
    $allowedModes = ['online', 'offline', 'hybrid'];
    if (empty($data['mode']) || !in_array($data['mode'], $allowedModes)) {
        $errors['mode'] = 'Invalid mode selected.';
    } else {
        $clean['mode'] = $data['mode'];
    }

    // Max teams
    if (!isset($data['max_teams']) || !ctype_digit((string)$data['max_teams'])) {
        $errors['max_teams'] = 'Max teams must be a positive number.';
    } else {
        $clean['max_teams'] = (int) $data['max_teams'];
        if ($clean['max_teams'] <= 1) {
            $errors['max_teams'] = 'Max teams must be greater than 1.';
        }
    }

    // Description
    if (!empty($data['description'])) {
        if (strlen($data['description']) > 1000) {
            $errors['description'] = 'Description too long (max 1000 characters).';
        } else {
            $clean['description'] = htmlspecialchars(trim($data['description']));
        }
    } else {
        $clean['description'] = null;
    }

    return [$clean, $errors];
}

    private function parseDateTime(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }

        return null;
    }

    private function normalizeStatus(string $status): ?string
    {
        $status = strtolower(trim($status));
        return in_array($status, ['upcoming', 'ongoing', 'finished'], true) ? $status : null;
    }

    private function registeredCount(int $tournamentId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tournament_participation WHERE tournament_id = :id AND status = :status',
            ['id' => $tournamentId, 'status' => 'registered']
        );
    }

    private function isRegistered(int $tournamentId, int $userId): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM tournament_participation
             WHERE tournament_id = :tournament_id AND user_id = :user_id AND status = :status
             LIMIT 1',
            ['tournament_id' => $tournamentId, 'user_id' => $userId, 'status' => 'registered']
        );
    }

    private function jsonSuccess(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function jsonError(string $message, int $status = 400, array $data = []): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function fetchTournamentsForDashboard(array $filters = [], string $sort = 'created_at', string $order = 'desc'): array
    {
        $sql = "SELECT
            t.id,
            t.name,
            t.status,
            t.start_datetime,
            t.end_datetime,
            t.region,
            t.mode,
            t.max_teams,
            t.description,
            t.created_at,
            COUNT(tp.participation_id) AS registered_teams
        FROM tournament t
        LEFT JOIN tournament_participation tp
            ON tp.tournament_id = t.id
            AND tp.status = 'registered'";
        
        $where = [];
        $params = [];
        
        // Add search filter
        if (!empty($filters['search'])) {
            $where[] = "(t.name LIKE :search OR t.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Add status filter
        if (!empty($filters['status'])) {
            $where[] = "t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        // Add region filter
        if (!empty($filters['region'])) {
            $where[] = "t.region = :region";
            $params['region'] = $filters['region'];
        }
        
        // Add mode filter
        if (!empty($filters['mode'])) {
            $where[] = "t.mode = :mode";
            $params['mode'] = $filters['mode'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY t.id";
        
        // Add sorting
        $allowedSorts = ['start_datetime', 'name', 'status', 'created_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'created_at';
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY t.$sort $order, t.id DESC";
        
        return $this->connection->fetchAllAssociative($sql, $params);
    }

    private function getFilterOptions(): array
    {
        $options = [
            'status' => [],
            'region' => [],
            'mode' => [],
        ];
        
        // Get unique statuses
        $statuses = $this->connection->fetchFirstColumn('SELECT DISTINCT status FROM tournament WHERE status IS NOT NULL ORDER BY status');
        $options['status'] = array_values($statuses);
        
        // Get unique regions
        $regions = $this->connection->fetchFirstColumn('SELECT DISTINCT region FROM tournament WHERE region IS NOT NULL AND region != "" ORDER BY region');
        $options['region'] = array_values($regions);
        
        // Get unique modes
        $modes = $this->connection->fetchFirstColumn('SELECT DISTINCT mode FROM tournament WHERE mode IS NOT NULL AND mode != "" ORDER BY mode');
        $options['mode'] = array_values($modes);
        
        return $options;
    }

    
}

