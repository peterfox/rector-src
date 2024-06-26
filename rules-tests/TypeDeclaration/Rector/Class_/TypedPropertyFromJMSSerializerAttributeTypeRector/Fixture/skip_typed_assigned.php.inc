<?php

namespace Rector\Tests\TypeDeclaration\Rector\Class_\TypedPropertyFromJMSSerializerAttributeTypeRector\Fixture;

final class SkipTypedAssigned
{
    #[\JMS\Serializer\Annotation\Type('string')]
    private $name;

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
