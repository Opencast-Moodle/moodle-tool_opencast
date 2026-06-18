<?php
namespace OpencastApi\Auth\JWT;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\ClaimsFormatter;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Ecdsa\Sha384;
use Lcobucci\JWT\Signer\Eddsa;
use Lcobucci\JWT\Signer\Key\InMemory;

use Lcobucci\JWT\Encoding\CannotDecodeContent;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\InvalidTokenStructure;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\UnsupportedHeaderFound;
use Lcobucci\JWT\UnencryptedToken;

class OcJwtHandler
{
    /** @var int token expiration duration in seconds */
    private int $expDuration = 15; // Default to 15 seconds

    /** @var Configuration configuration instance */
    private Configuration $config;

    /** @var Signer signer instance */
    private Signer $signer;

    /** @var array list of supported algorithms (Asymmetric) */
    const SUPPORTED_ALGORITHMS = [
        'ES256' => Sha256::class,
        'ES384' => Sha384::class,
        'EdDSA' => Eddsa::class
    ];

    /** @var string default algorithm */
    const DEFAULT_ALGORITHM = 'ES256';

    /** @var string default audience */
    const AUDIENCE = 'opencast-php-library';

    /**
     * Constructor
     */
    public function __construct(string $privateKeyString, ?string $algorithmKey = null, ?int $expDuration = null)
    {
        $algorithmKey = $algorithmKey ?? self::DEFAULT_ALGORITHM;

        if (!array_key_exists($algorithmKey, self::SUPPORTED_ALGORITHMS)) {
            throw new \InvalidArgumentException("JWT: Unsupported algorithm: $algorithmKey");
        }

        if (empty($privateKeyString)) {
            throw new \InvalidArgumentException("JWT: Private key is required");
        }

        if (!empty($expDuration) && $expDuration > 0) {
            $this->expDuration = $expDuration;
        }

        $signerClassname = self::SUPPORTED_ALGORITHMS[$algorithmKey];
        $this->signer = new $signerClassname();

        $signingKey = InMemory::plainText($privateKeyString);

        // Randomly generated, but not effectively in-use.
        $verificationKey = InMemory::base64Encoded(base64_encode(random_bytes(32)));

        $configuration = Configuration::forAsymmetricSigner(
            $this->signer,
            $signingKey,
            $verificationKey
        );

        // Register Opencast specific builder.
        $version = \Composer\InstalledVersions::getVersion('lcobucci/jwt');
        if (version_compare($version, '5.5', '>=')) {
            $configuration = $configuration->withBuilderFactory(
                static function (ClaimsFormatter $formatter) : Builder {
                    return OcJwtBuilder::new(new JoseEncoder(), $formatter);
                }
            );
        } else {
            $configuration->setBuilderFactory(
                static function (ClaimsFormatter $formatter) : Builder {
                    return OcJwtBuilder::new(new JoseEncoder(), $formatter);
                }
            );
        }

        $this->config = $configuration;
    }

    /**
     * Gets the supported JWT algorithms.
     *
     * @return array The supported JWT algorithms.
     */
    public static function getSupportedAlgorithms(): array
    {
        return array_keys(self::SUPPORTED_ALGORITHMS);
    }

    /**
     * Issues a JWT token for the given claims.
     *
     * @param OcJwtClaim $claim The claims to include in the token.
     * @return string The signed JWT token.
     */
    public function issueToken(OcJwtClaim $claim): string
    {
        if (!$claim->hasExp()) {
            $expiryFormatted = OcJwtClaim::generateFormattedDateTimeObject((int) $this->expDuration);
            $claim->setExp($expiryFormatted);
        }

        $builder = $this->config->builder()->setOcClaims($claim);

        return $builder->getToken($this->config->signer(), $this->config->signingKey())->toString();
    }

    /**
     * Validates a JWT token.
     *
     * @param string $tokenString The JWT token string to validate.
     * @return bool True if the token is valid, false otherwise.
     */
    public function validateToken(string $tokenString): bool
    {
        $token = $this->tokenParser($tokenString);
        if ($token === null) {
            return false;
        }

        return $this->config->validator()->validate($token, new OcJwtValidationConstraint());
    }

    /**
     * Parses a JWT token string into a Token object.
     *
     * @param string $tokenString The JWT token string to parse.
     * @return Token|null The parsed Token object, or null if parsing failed.
     */
    private function tokenParser(string $tokenString): ?Token
    {
        $token = null;
        try {
            $token = $this->config->parser()->parse($tokenString);
        } catch (CannotDecodeContent | InvalidTokenStructure | UnsupportedHeaderFound $e) {
            throw new \InvalidArgumentException('JWT: Unable to parse token: ' . $e->getMessage());
        }
        assert($token instanceof UnencryptedToken, 'Token must be an UnencryptedToken');

        return $token;
    }

    /**
     * Returns the OcJwtClaim object from encoded token string.
     *
     * @param string $tokenString The JWT token string to convert to OcJwtClaim.
     * @return OcJwtClaim|null The OcJwtClaim object or null if failed.
     */
    public function getOcJwtClaimFromTokenString(string $tokenString): ?OcJwtClaim {
        try {
            $token = $this->tokenParser($tokenString);
            if ($token === null) {
                return null;
            }
            return OcJwtClaim::convertFromTokenWithValidation($token);
        } catch (\Throwable $th) {
            return null;
        }
    }
}
