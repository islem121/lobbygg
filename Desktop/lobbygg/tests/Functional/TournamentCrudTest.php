<?php

namespace App\Tests\Functional;

use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

class TournamentCrudTest extends WebTestCase
{
    private const ADMIN_EMAIL = 'admin@lobbygg.local';
    private const TEST_PREFIX = '[CRUD-TEST] ';

    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->cleanupTestTournaments();
        $this->ensureAdminExists();
        $this->client->disableReboot();
    }

    public function testBackofficeTournamentCrud(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $crawler = $this->client->request('GET', '/admin/tournament/new');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->submitForm('Create', [
            'tournament[title]' => self::TEST_PREFIX.'BO',
            'tournament[description]' => 'Backoffice CRUD test',
            'tournament[startDate]' => '2026-03-01T10:00',
            'tournament[endDate]' => '2026-03-02',
            'tournament[maxPlayers]' => 16,
            'tournament[status]' => 'upcoming',
        ]);
        self::assertResponseRedirects('/admin/tournament/');

        $tournament = $this->findTournamentByTitle(self::TEST_PREFIX.'BO');
        self::assertNotNull($tournament);

        $this->client->request('GET', '/admin/tournament/'.$tournament->getId().'/edit');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->client->submitForm('Update', [
            'tournament[title]' => self::TEST_PREFIX.'BO updated',
            'tournament[description]' => 'Backoffice CRUD test updated',
            'tournament[startDate]' => '2026-03-03T11:30',
            'tournament[endDate]' => '2026-03-04',
            'tournament[maxPlayers]' => 20,
            'tournament[status]' => 'live',
        ]);
        self::assertResponseRedirects('/admin/tournament/');

        $updated = $this->findTournamentByTitle(self::TEST_PREFIX.'BO updated');
        self::assertNotNull($updated);

        $this->client->request('GET', '/admin/tournament/'.$updated->getId());
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $crawler = $this->client->request('GET', '/admin/tournament/'.$updated->getId().'/edit');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $token = $crawler->filter('input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/tournament/'.$updated->getId(), [
            '_token' => $token,
        ]);
        self::assertResponseRedirects('/admin/tournament/');
        self::assertNull($this->findTournamentByTitle(self::TEST_PREFIX.'BO updated'));
    }

    public function testFrontofficeTournamentCrudAsAdmin(): void
    {
        $this->client->loginUser($this->getAdminUser());

        $this->client->request('GET', '/tournaments/new');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->client->submitForm('Create', [
            'tournament[title]' => self::TEST_PREFIX.'FO',
            'tournament[description]' => 'Frontoffice CRUD test',
            'tournament[startDate]' => '2026-04-01T09:00',
            'tournament[endDate]' => '2026-04-02',
            'tournament[maxPlayers]' => 12,
            'tournament[status]' => 'upcoming',
        ]);
        self::assertResponseRedirects('/tournaments');

        $tournament = $this->findTournamentByTitle(self::TEST_PREFIX.'FO');
        self::assertNotNull($tournament);

        $this->client->request('GET', '/tournaments/'.$tournament->getId().'/edit');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->client->submitForm('Update', [
            'tournament[title]' => self::TEST_PREFIX.'FO updated',
            'tournament[description]' => 'Frontoffice CRUD test updated',
            'tournament[startDate]' => '2026-04-05T14:00',
            'tournament[endDate]' => '2026-04-06',
            'tournament[maxPlayers]' => 24,
            'tournament[status]' => 'finished',
        ]);
        self::assertResponseRedirects('/tournaments/'.$tournament->getId());

        $updated = $this->findTournamentByTitle(self::TEST_PREFIX.'FO updated');
        self::assertNotNull($updated);

        $this->client->request('GET', '/tournaments/'.$updated->getId());
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $crawler = $this->client->request('GET', '/tournaments');
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $deleteForm = $crawler->filter(sprintf('form[action="/tournaments/%d/delete"] input[name="_token"]', $updated->getId()));
        self::assertCount(1, $deleteForm);
        $token = $deleteForm->attr('value');

        $this->client->request('POST', '/tournaments/'.$updated->getId().'/delete', [
            '_token' => $token,
        ]);
        self::assertResponseRedirects('/tournaments');
        self::assertNull($this->findTournamentByTitle(self::TEST_PREFIX.'FO updated'));
    }

    protected function tearDown(): void
    {
        $this->cleanupTestTournaments();
        parent::tearDown();
    }

    private function ensureAdminExists(): void
    {
        $repo = $this->em->getRepository(User::class);
        $admin = $repo->findOneBy(['email' => self::ADMIN_EMAIL]);

        if ($admin instanceof User) {
            if ($admin->getRole() !== User::ROLE_ADMIN) {
                $admin->setRole(User::ROLE_ADMIN);
                $this->em->flush();
            }
            return;
        }

        $admin = new User();
        $admin->setEmail(self::ADMIN_EMAIL);
        $admin->setUsername('lobbyadmin');
        $admin->setPassword(password_hash('Admin123!', PASSWORD_BCRYPT));
        $admin->setRole(User::ROLE_ADMIN);
        $admin->setNom('Admin');
        $admin->setPrenom('Lobby');

        $this->em->persist($admin);
        $this->em->flush();
    }

    private function getAdminUser(): User
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => self::ADMIN_EMAIL]);
        return $user;
    }

    private function cleanupTestTournaments(): void
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete(Tournament::class, 't')
            ->where('t.title LIKE :prefix')
            ->setParameter('prefix', self::TEST_PREFIX.'%')
            ->getQuery()
            ->execute();
    }

    private function findTournamentByTitle(string $title): ?Tournament
    {
        return $this->em->getRepository(Tournament::class)->findOneBy(['title' => $title]);
    }
}
