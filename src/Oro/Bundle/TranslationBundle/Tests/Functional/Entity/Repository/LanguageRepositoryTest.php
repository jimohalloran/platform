<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslationUsers;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

class LanguageRepositoryTest extends WebTestCase
{
    /** @var EntityManager */
    protected $em;

    /** @var LanguageRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadTranslations::class]);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(Language::class);
        $this->repository = $this->em->getRepository(Language::class);

        /* @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('doctrine')->getManagerForClass(User::class)
            ->getRepository(User::class);

        /* @var User $user */
        $user = $userRepository->findOneBy(['username' => LoadTranslationUsers::TRANSLATOR_USERNAME]);

        $token = new UsernamePasswordOrganizationToken($user, false, 'k', $user->getOrganization(), $user->getRoles());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testGetAvailableLanguageCodesAsArrayKeys(): void
    {
        $defaultAndLoadedLanguageCodes = \array_merge(
            [Configuration::DEFAULT_LANGUAGE],
            \array_keys(LoadLanguages::LANGUAGES)
        );

        static::assertEqualsCanonicalizing(
            \array_fill_keys($defaultAndLoadedLanguageCodes, true),
            $this->repository->getAvailableLanguageCodesAsArrayKeys()
        );
    }

    public function testGetAvailableLanguagesByCurrentUser()
    {
        /* @var AclHelper $aclHelper */
        $aclHelper = $this->getContainer()->get('oro_security.acl_helper');

        $this->assertEquals(
            [
                $this->getReference(LoadLanguages::LANGUAGE3)
            ],
            $this->repository->getAvailableLanguagesByCurrentUser($aclHelper)
        );
    }

    public function testGetTranslationsForExport()
    {
        $expected = [
            [
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key' => LoadTranslations::TRANSLATION_KEY_1,
                'value' => LoadTranslations::TRANSLATION_KEY_1,
                'english_translation' => LoadTranslations::TRANSLATION_KEY_1,
                'is_translated' => 1,
            ],
            [
                'domain' => LoadTranslations::TRANSLATION_KEY_DOMAIN,
                'key' => LoadTranslations::TRANSLATION_KEY_2,
                'value' => LoadTranslations::TRANSLATION_KEY_2,
                'english_translation' => LoadTranslations::TRANSLATION_KEY_2,
                'is_translated' => 1,
            ],
        ];

        $result = $this->repository->getTranslationsForExport(LoadLanguages::LANGUAGE1);

        foreach ($expected as $translation) {
            $this->assertTrue(in_array($translation, $result));
        }
    }

    /**
     * @param Language[] $expected
     * @param bool $enabled
     *
     * @dataProvider getLanguagesDataProvider
     */
    public function testGetLanguages(array $expected, $enabled)
    {
        $current = array_map(function (Language $lang) {
            return $lang->getCode();
        }, $this->repository->getLanguages($enabled));

        $this->assertEquals($expected, $current);
    }

    /**
     * @return \Generator
     */
    public function getLanguagesDataProvider()
    {
        yield 'only enabled' => [
            'expected' => [
                'en',
                LoadLanguages::LANGUAGE2,
                LoadLanguages::LANGUAGE3,
            ],
            'enabled' => true,
        ];
        yield 'all' => [
            'expected' => [
                'en',
                LoadLanguages::LANGUAGE1,
                LoadLanguages::LANGUAGE2,
                LoadLanguages::LANGUAGE3,
            ],
            'enabled' => false,
        ];
    }
}
