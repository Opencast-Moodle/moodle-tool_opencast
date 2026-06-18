<?php
namespace OpencastApi\Auth\JWT;

use DateTimeImmutable;
use Lcobucci\JWT\Builder as BuilderInterface;
use Lcobucci\JWT\ClaimsFormatter;
use Lcobucci\JWT\Encoder;
use Lcobucci\JWT\Encoding\CannotEncodeContent;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;

final class OcJwtBuilder implements BuilderInterface
{
    /** @var array<non-empty-string, mixed> */
    private array $headers = ['typ' => 'JWT', 'alg' => null];

    /** @var array<non-empty-string, mixed> */
    private array $claims = [];

    /**
     * @inheritDoc
     *
     * Use {@see self::new()} instead of directly instantiating with `new OcJwtBuilder()` as it is deprecated.
     */
    public function __construct(private readonly Encoder $encoder, private readonly ClaimsFormatter $claimFormatter)
    {
    }

    /**
     * @inheritDoc
     */
    public static function new(Encoder $encoder, ClaimsFormatter $claimFormatter): self
    {
        return new self($encoder, $claimFormatter);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function permittedFor(string ...$audiences): BuilderInterface
    {
        $configured = $this->claims[RegisteredClaims::AUDIENCE] ?? [];
        $toAppend = array_diff($audiences, $configured);

        return $this->setClaim(RegisteredClaims::AUDIENCE, array_merge($configured, $toAppend));
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function expiresAt(DateTimeImmutable $expiration): BuilderInterface
    {
        return $this->setClaim(RegisteredClaims::EXPIRATION_TIME, $expiration);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function identifiedBy(string $id): BuilderInterface
    {
        return $this->setClaim(RegisteredClaims::ID, $id);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function issuedAt(DateTimeImmutable $issuedAt): BuilderInterface
    {
        return $this->setClaim(RegisteredClaims::ISSUED_AT, $issuedAt);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function issuedBy(string $issuer): BuilderInterface
    {
        return $this->setClaim(RegisteredClaims::ISSUER, $issuer);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function canOnlyBeUsedAfter(DateTimeImmutable $notBefore): BuilderInterface
    {
        return $this->setClaim(RegisteredClaims::NOT_BEFORE, $notBefore);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function relatedTo(string $subject): BuilderInterface
    {
        return $this->setClaim(RegisteredClaims::SUBJECT, $subject);
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function withHeader(string $name, mixed $value): BuilderInterface
    {
        $new = clone $this;
        $new->headers[$name] = $value;

        return $new;
    }

    /**
     * @inheritDoc
     * @pure
     */
    public function withClaim(string $name, mixed $value): BuilderInterface
    {
        if (in_array($name, RegisteredClaims::ALL, true)) {
            throw RegisteredClaimGiven::forClaim($name);
        }

        return $this->setClaim($name, $value);
    }

    /**
     * @param non-empty-string $name
     */
    private function setClaim(string $name, mixed $value): BuilderInterface
    {
        $new                = clone $this;
        $new->claims[$name] = $value;

        return $new;
    }

    /**
     * @param array<non-empty-string, mixed> $items
     *
     * @throws CannotEncodeContent When data cannot be converted to JSON.
     */
    private function encode(array $items): string
    {
        return $this->encoder->base64UrlEncode(
            $this->encoder->jsonEncode($items),
        );
    }

    /**
     * @inheritDoc
     */
    public function getToken(Signer $signer, Key $key): UnencryptedToken
    {
        $headers = $this->headers;
        $headers['alg'] = $signer->algorithmId();

        $encodedHeaders = $this->encode($headers);
        $encodedClaims = $this->encode($this->claimFormatter->formatClaims($this->claims));

        $signature = $signer->sign($encodedHeaders . '.' . $encodedClaims, $key);
        $encodedSignature = $this->encoder->base64UrlEncode($signature);

        return new Plain(
            new DataSet($headers, $encodedHeaders),
            new DataSet($this->claims, $encodedClaims),
            new Signature($signature, $encodedSignature),
        );
    }
    /**
     * Sets Opencast specific claims at one go with opencast jwt claim specifications.
     *
     * @param OcJwtClaim $ocClaims
     *
     * @return BuilderInterface
     */
    public function setOcClaims(OcJwtClaim $ocClaims): BuilderInterface
    {
        $new = clone $this;

        foreach ($ocClaims->toArray() as $name => $value) {
            if (!in_array($name, OcJwtClaim::OC_CLAIMS, true)) {
                throw new \InvalidArgumentException(sprintf('Claim "%s" is not a valid Opencast claim.', $name));
            }
            $new->claims[$name] = $value;
        }

        return $new;
    }
}

