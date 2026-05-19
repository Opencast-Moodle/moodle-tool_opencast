<?php
namespace OpencastApi\Auth\JWT;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;
use DateTimeImmutable;
use Throwable;

final class OcJwtValidationConstraint implements Constraint
{
    /**
     * @inheritDoc
     */
    public function assert(Token $token): void
    {
        if (!$token instanceof UnencryptedToken) {
            throw new ConstraintViolation('You should pass a plain token');
        }

        $now = new DateTimeImmutable();
        if (!$token->claims()->has(Token\RegisteredClaims::EXPIRATION_TIME)) {
            throw ConstraintViolation::error('"Expiration Time" claim missing', $this);
        }

        if ($token->isExpired($now)) {
            throw ConstraintViolation::error('The token is expired', $this);
        }

        try {
            $ocClaim = OcJwtClaim::convertFromTokenWithValidation($token);
        } catch (Throwable $th) {
            throw ConstraintViolation::error('Invalid Opencast specific claims!', $this);
        }

        assert($ocClaim instanceof OcJwtClaim, 'Invalid Opencast JWT claim!');
    }
}
