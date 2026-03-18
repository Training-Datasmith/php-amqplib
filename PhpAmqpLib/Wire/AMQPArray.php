<?php

namespace PhpAmqpLib\Wire;

class AMQPArray extends AMQPAbstractCollection
{

    public function __construct(?array $data = null)
    {
        parent::__construct(empty($data) ? null : array_values($data));
    }

    final public function getType(): int
    {
        return self::T_ARRAY;
    }

    /**
     * @param mixed $val
     * @param int|null $type
     * @return $this
     */
    public function push($val, $type = null): self
    {
        $this->setValue($val, $type);

        return $this;
    }
}
