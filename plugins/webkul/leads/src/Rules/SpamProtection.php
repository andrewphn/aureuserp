<?php

namespace Webkul\Lead\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Content-based spam detection rule
 *
 * Uses scoring system to detect spam patterns:
 * - Disposable email domains (+5)
 * - Spam keywords (+4)
 * - Excessive capitals (+2)
 * - Repeating characters (+2)
 * - Gibberish text (+3)
 * - Random patterns (+3)
 *
 * Score >= 5 triggers spam rejection
 */
class SpamProtection implements ValidationRule
{
    protected string $fieldType;

    protected int $spamScore = 0;

    protected array $reasons = [];

    /**
     * Disposable email domains
     */
    protected array $disposableEmailDomains = [
        'guerrillamail.com',
        'mailinator.com',
        'tempmail.com',
        '10minutemail.com',
        'throwaway.email',
        'fakeinbox.com',
        'trashmail.com',
        'yopmail.com',
        'getnada.com',
        'dispostable.com',
        'maildrop.cc',
        'temp-mail.org',
    ];

    /**
     * Spam keywords
     */
    protected array $spamKeywords = [
        'cialis',
        'viagra',
        'casino',
        'lottery',
        'prize',
        'winner',
        'bitcoin',
        'cryptocurrency',
        'investment opportunity',
        'make money fast',
        'work from home',
        'free offer',
        'click here',
        'limited time',
        'act now',
        'guaranteed',
        'no obligation',
    ];

    public function __construct(string $fieldType = 'text')
    {
        $this->fieldType = $fieldType;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $this->spamScore = 0;
        $this->reasons = [];

        // Run checks based on field type
        match ($this->fieldType) {
            'email' => $this->checkEmail($value),
            'name' => $this->checkName($value),
            'message', 'text' => $this->checkText($value),
            'company' => $this->checkCompany($value),
            default => $this->checkText($value),
        };

        // Fail if spam score exceeds threshold
        if ($this->spamScore >= 5) {
            $fail('This submission appears to be spam.');
        }
    }

    /**
     * Check email for spam patterns
     */
    protected function checkEmail(string $email): void
    {
        $email = strtolower($email);

        // Check for disposable email domains
        $domain = substr($email, strpos($email, '@') + 1);
        if (in_array($domain, $this->disposableEmailDomains)) {
            $this->addScore(5, 'Disposable email domain');
        }

        // Check for random-looking email patterns (e.g., abc123xyz@)
        $localPart = substr($email, 0, strpos($email, '@'));
        if (preg_match('/^[a-z]{3,5}\d{3,6}[a-z]{2,4}$/i', $localPart)) {
            $this->addScore(3, 'Random email pattern');
        }

        // Check for excessive numbers in email
        if (preg_match('/\d{6,}/', $localPart)) {
            $this->addScore(2, 'Excessive numbers in email');
        }
    }

    /**
     * Check name for spam patterns
     */
    protected function checkName(string $name): void
    {
        // Check for gibberish (bad consonant/vowel ratio)
        if ($this->isGibberish($name)) {
            $this->addScore(3, 'Gibberish name');
        }

        // Check for repeating characters
        if ($this->hasRepeatingCharacters($name)) {
            $this->addScore(2, 'Repeating characters');
        }

        // Check for random patterns
        if (preg_match('/^[a-z]{2,3}[A-Z][a-z]{2,3}[A-Z]/', $name)) {
            $this->addScore(3, 'Random name pattern');
        }
    }

    /**
     * Check text/message for spam patterns
     */
    protected function checkText(string $text): void
    {
        $lowerText = strtolower($text);

        // Check for spam keywords
        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($lowerText, $keyword)) {
                $this->addScore(4, "Spam keyword: {$keyword}");
                break; // Only count once
            }
        }

        // Check for excessive capitals (more than 60%)
        $alphaChars = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($alphaChars) > 10) {
            $upperCount = strlen(preg_replace('/[^A-Z]/', '', $alphaChars));
            if ($upperCount / strlen($alphaChars) > 0.6) {
                $this->addScore(2, 'Excessive capitals');
            }
        }

        // Check for repeating characters
        if ($this->hasRepeatingCharacters($text)) {
            $this->addScore(2, 'Repeating characters');
        }

        // Check for URL spam
        if (preg_match_all('/https?:\/\/[^\s]+/', $text, $matches)) {
            $urlCount = count($matches[0]);
            if ($urlCount > 2) {
                $this->addScore(3, 'Multiple URLs');
            }
        }

        // Check for HTML content
        if (preg_match('/<[a-z][\s\S]*>/i', $text)) {
            $this->addScore(4, 'HTML content');
        }
    }

    /**
     * Check company name for spam patterns
     */
    protected function checkCompany(string $company): void
    {
        // Check for spam keywords in company name
        $lowerCompany = strtolower($company);
        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($lowerCompany, $keyword)) {
                $this->addScore(4, "Spam keyword in company: {$keyword}");
                break;
            }
        }

        // Check for gibberish
        if ($this->isGibberish($company)) {
            $this->addScore(3, 'Gibberish company name');
        }
    }

    /**
     * Check if text is gibberish (bad consonant/vowel ratio)
     */
    protected function isGibberish(string $text): bool
    {
        $text = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($text) < 5) {
            return false;
        }

        $vowels = preg_match_all('/[aeiouAEIOU]/', $text);
        $consonants = strlen($text) - $vowels;

        // Very low or very high vowel ratio indicates gibberish
        $ratio = $vowels / strlen($text);

        return $ratio < 0.15 || $ratio > 0.7;
    }

    /**
     * Check for repeating characters (5+ same character)
     */
    protected function hasRepeatingCharacters(string $text): bool
    {
        return (bool) preg_match('/(.)\1{4,}/', $text);
    }

    /**
     * Add to spam score
     */
    protected function addScore(int $points, string $reason): void
    {
        $this->spamScore += $points;
        $this->reasons[] = $reason;
    }
}
