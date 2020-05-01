<?php
declare(strict_types=1);

namespace Sandstorm\DynamicImage\FusionObjects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\ObjectAccess;
use Ramsey\Uuid\Uuid;
use Sandstorm\DynamicImage\Domain\Model\DynamicVipsAdjustment;

class VipsVariantImplementation extends AbstractFusionObject
{

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function getImage(): ?ImageInterface
    {
        return $this->fusionValue('image');
    }

    public function getIdentifier(): string
    {
        return $this->fusionValue('identifier');
    }

    public function getRules(): array
    {
        return $this->fusionValue('rules');
    }

    public function evaluate()
    {
        $image = $this->getImage();
        if ($image === null) {
            return null;
        }

        if ($image instanceof Image) {
            $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, $this->persistenceManager->getIdentifierByObject($image) . $this->getIdentifier())->toString();

            $imageVariant = $this->assetRepository->findByIdentifier($uuid);
            if (!$imageVariant) {
                $imageVariant = new ImageVariant($image);
                ObjectAccess::setProperty($imageVariant, 'Persistence_Object_Identifier', $uuid, true);
            }
        } else {
            /* @var $originalImageVariant \Neos\Media\Domain\Model\ImageVariant */
            $originalImageVariant = $image;

            $uuid = Uuid::uuid5(Uuid::NAMESPACE_URL, $this->persistenceManager->getIdentifierByObject($originalImageVariant->getOriginalAsset()) . $this->getIdentifier())->toString();

            $imageVariant = $this->assetRepository->findByIdentifier($uuid);
            if (!$imageVariant) {
                $imageVariant = new ImageVariant($originalImageVariant->getOriginalAsset());
                ObjectAccess::setProperty($imageVariant, 'Persistence_Object_Identifier', $uuid, true);
            }

            foreach ($originalImageVariant->getAdjustments() as $adjustment) {
                $imageVariant->addAdjustment(clone $adjustment);
            }
        }

        $dynamicVipsAdjustment = new DynamicVipsAdjustment();
        $dynamicVipsAdjustment->setRules($this->getRules());
        $imageVariant->addAdjustment($dynamicVipsAdjustment);

        if ($this->persistenceManager->isNewObject($imageVariant)) {
            $this->persistenceManager->add($imageVariant);
        } else {
            $this->persistenceManager->update($imageVariant);
        }

        $this->persistenceManager->persistAll();

        return $imageVariant;
    }
}
