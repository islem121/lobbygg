<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;

class IntelligentChatService
{
    private const LANGUAGE_BY_ORIGIN = [
        'fr' => 'fr',
        'france' => 'fr',
        'belgique' => 'fr',
        'suisse' => 'fr',
        'canada' => 'fr',
        'tn' => 'ar',
        'tunisie' => 'ar',
        'tunisia' => 'ar',
        'algerie' => 'ar',
        'algeria' => 'ar',
        'maroc' => 'ar',
        'morocco' => 'ar',
        'egypte' => 'ar',
        'egypt' => 'ar',
        'kr' => 'ko',
        'coree du sud' => 'ko',
        'corée du sud' => 'ko',
        'south korea' => 'ko',
        'korea' => 'ko',
        'us' => 'en',
        'usa' => 'en',
        'united states' => 'en',
        'united states of america' => 'en',
        'etats unis' => 'en',
        'états unis' => 'en',
        'etats-unis' => 'en',
        'états-unis' => 'en',
        'en' => 'en',
        'uk' => 'en',
        'gb' => 'en',
        'united kingdom' => 'en',
        'england' => 'en',
    ];

    private const TOXIC_PATTERNS = [
        '/\b(nul|nullos|stupide|idiot|imbecile|imbécile|con|connard)\b/i' => 'peut progresser',
        '/\b(merde|putain)\b/i' => 'oups',
        '/\b(fuck|shit|suck)\b/i' => 'let us keep it constructive',
    ];

    private const ENCOURAGEMENT_SUFFIX = [
        'fr' => ' Courage, tu peux y arriver.',
        'en' => ' Keep going, you can do this.',
        'ar' => ' استمر، أنت قادر على ذلك.',
        'ko' => ' 계속해요, 해낼 수 있어요.',
    ];

    private const TOXICITY_NOTICE = [
        'fr' => 'Ton message a été ajusté pour maintenir un environnement sain. Respire, reformule, et continue.',
        'en' => 'Your message was adjusted to keep the chat healthy. Take a breath, reframe, and keep playing.',
        'ar' => 'تم تعديل رسالتك للحفاظ على بيئة صحية. خذ نفسا وأعد الصياغة وواصل اللعب.',
        'ko' => '건강한 대화를 위해 메시지가 조정되었어요. 잠시 숨 고르고 다시 표현해 보세요.',
    ];

    private const MICRO_TRANSLATIONS = [
        'ko' => [
            'bonjour' => '안녕하세요',
            'salut' => '안녕',
            'merci' => '감사합니다',
            'bon jeu' => '좋은 게임',
            'a bientot' => '곧 봐요',
            'à bientôt' => '곧 봐요',
        ],
        'ar' => [
            'bonjour' => 'مرحبا',
            'salut' => 'أهلا',
            'merci' => 'شكرا',
            'bon jeu' => 'لعبة موفقة',
            'a bientot' => 'أراك قريبا',
            'à bientôt' => 'أراك قريبا',
        ],
        'en' => [
            'je' => 'i',
            'tu' => 'you',
            'il' => 'he',
            'elle' => 'she',
            'nous' => 'we',
            'vous' => 'you',
            'ils' => 'they',
            'elles' => 'they',
            'suis' => 'am',
            'es' => 'are',
            'est' => 'is',
            'sommes' => 'are',
            'etes' => 'are',
            'êtes' => 'are',
            'bonjour' => 'hello',
            'salut' => 'hi',
            'merci' => 'thanks',
            'bon jeu' => 'good game',
            'au revoir' => 'goodbye',
            'bien joue' => 'well played',
            'bien joué' => 'well played',
            'comment ca va' => 'how are you',
            'comment ça va' => 'how are you',
            'je vais bien' => 'i am fine',
            'a bientot' => 'see you soon',
            'à bientôt' => 'see you soon',
        ],
        'fr' => [
            'hello' => 'bonjour',
            'thanks' => 'merci',
            'good game' => 'bon jeu',
            'see you soon' => 'a bientot',
        ],
    ];

    public function processMessage(Message $message): void
    {
        $analysis = $this->processOutgoingMessage(
            (string) $message->getContent(),
            $message->getSender(),
            $message->getReceiver()
        );

        $message->setOriginalContent($analysis['originalContent']);
        $message->setContent($analysis['content']);
        $message->setIsToxic($analysis['isToxic']);
    }

    public function processOutgoingMessage(string $message, User $sender, User $receiver): array
    {
        $original = trim($message);
        $working = $original;

        $isToxic = $this->detectToxicity($working);
        if ($isToxic) {
            $working = $this->rewriteMessage($working);
        }

        $emotion = $this->analyzeEmotion($working . ' ' . (string) $receiver->getMood());
        $targetLanguage = $this->getLanguageFromOrigin($receiver);
        $sourceLanguage = $this->getLanguageFromOrigin($sender);

        $translated = $working;
        $isTranslated = false;
        if ($sourceLanguage !== $targetLanguage) {
            $translated = $this->translateMessage($working, $targetLanguage);
            $isTranslated = true;
        }

        $emotionAdjusted = false;
        if (in_array($emotion, ['fatigue', 'demotivation'], true)) {
            $translated = $this->applyEncouragingTone($translated, $targetLanguage);
            $emotionAdjusted = true;
        }

        return [
            'originalContent' => $original,
            'content' => $translated,
            'isToxic' => $isToxic,
            'playerNotice' => $isToxic ? $this->getToxicityNotice($sourceLanguage) : null,
            'isTranslated' => $isTranslated,
            'emotionAdjusted' => $emotionAdjusted,
            'sourceLanguage' => $sourceLanguage,
            'targetLanguage' => $targetLanguage,
        ];
    }

    public function getLanguageFromOrigin(User $user): string
    {
        $origin = $this->normalizeText((string) ($user->getOrigin() ?? ''));
        $originCompact = str_replace([' ', '-', '_'], '', $origin);

        if ($originCompact === 'us') {
            return 'en';
        }
        if ($originCompact === 'uk' || $originCompact === 'gb') {
            return 'en';
        }
        if ($originCompact === 'fr') {
            return 'fr';
        }
        if ($originCompact === 'tn') {
            return 'ar';
        }
        if ($originCompact === 'kr') {
            return 'ko';
        }

        if ($origin !== '' && isset(self::LANGUAGE_BY_ORIGIN[$origin])) {
            return self::LANGUAGE_BY_ORIGIN[$origin];
        }

        // Rule kept explicit for Tunisia.
        if (str_contains($origin, 'tunisie') || str_contains($origin, 'tunisia')) {
            return 'ar';
        }

        return 'fr';
    }

    public function translateMessage(string $message, string $targetLanguage): string
    {
        $translated = trim($message);
        $dictionary = self::MICRO_TRANSLATIONS[$targetLanguage] ?? [];

        // Longer keys first so multi-word phrases are translated before single words.
        uksort($dictionary, static fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($dictionary as $source => $target) {
            $translated = preg_replace('/\b' . preg_quote($source, '/') . '\b/i', $target, $translated) ?? $translated;
        }

        if ($translated === trim($message)) {
            // No external translation provider in this layer: we keep message semantic and tag the target language.
            $translated = '[' . strtoupper($targetLanguage) . '] ' . $translated;
        }

        return $translated;
    }

    public function analyzeEmotion(string $message): string
    {
        $value = $this->normalizeText($message);
        $fatigueSignals = ['fatigue', 'fatiguee', 'fatigué', 'fatiguee', 'tired', 'epuise', 'épuisé'];
        $demotivationSignals = ['j abandonne', 'j abandon', 'demotive', 'démotivé', 'demotivee', 'sad', 'triste', 'hopeless'];

        foreach ($fatigueSignals as $signal) {
            if (str_contains($value, $signal)) {
                return 'fatigue';
            }
        }

        foreach ($demotivationSignals as $signal) {
            if (str_contains($value, $signal)) {
                return 'demotivation';
            }
        }

        return 'neutral';
    }

    public function detectToxicity(string $message): bool
    {
        foreach (array_keys(self::TOXIC_PATTERNS) as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    public function rewriteMessage(string $message): string
    {
        $rewritten = $message;
        foreach (self::TOXIC_PATTERNS as $pattern => $replacement) {
            $rewritten = preg_replace($pattern, $replacement, $rewritten) ?? $rewritten;
        }

        return $rewritten;
    }

    public function getLanguageLabel(string $code): string
    {
        return match ($code) {
            'fr' => 'FR',
            'en' => 'EN',
            'ar' => 'AR',
            'ko' => 'KO',
            default => strtoupper($code),
        };
    }

    public function buildMessageMeta(Message $message, User $viewer): array
    {
        $sourceLang = $this->getLanguageFromOrigin($message->getSender());
        $targetLang = $this->getLanguageFromOrigin($message->getReceiver());

        $original = $message->getOriginalContent() ?: $message->getContent();
        $translated = $message->getContent();

        $isTranslated = $sourceLang !== $targetLang && $original !== $translated;
        // Always display the translated/processed version in chat UI.
        $displayContent = $translated;

        return [
            'aiAssisted' => $isTranslated || (bool) $message->isToxic(),
            'isTranslated' => $isTranslated,
            'sourceLanguage' => $sourceLang,
            'targetLanguage' => $targetLang,
            'sourceLabel' => $this->getLanguageLabel($sourceLang),
            'targetLabel' => $this->getLanguageLabel($targetLang),
            'displayContent' => $displayContent,
            'originalContent' => $original,
            'translatedContent' => $translated,
            'showModerationNotice' => (bool) $message->isToxic() && $message->getSender() === $viewer,
            'playerNotice' => ((bool) $message->isToxic() && $message->getSender() === $viewer) ? $this->getToxicityNotice($sourceLang) : null,
        ];
    }

    public function getToxicityNotice(string $language): string
    {
        return self::TOXICITY_NOTICE[$language] ?? self::TOXICITY_NOTICE['fr'];
    }

    private function applyEncouragingTone(string $message, string $language): string
    {
        $suffix = self::ENCOURAGEMENT_SUFFIX[$language] ?? self::ENCOURAGEMENT_SUFFIX['fr'];
        if (str_ends_with($message, $suffix)) {
            return $message;
        }

        return rtrim($message) . $suffix;
    }

    private function normalizeText(string $value): string
    {
        $value = trim(strtolower($value));
        $map = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ];

        return strtr($value, $map);
    }
}
