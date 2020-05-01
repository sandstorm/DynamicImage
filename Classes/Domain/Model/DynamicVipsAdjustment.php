<?php
declare(strict_types=1);

namespace Sandstorm\DynamicImage\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Imagine\Exception\RuntimeException;
use Imagine\Image\ImageInterface as ImagineImageInterface;
use Jcupitt\Vips\Exception;
use Jcupitt\Vips\Image as VipsImage;
use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\Adjustment\AbstractImageAdjustment;

/**
 * An adjustment for cropping an image
 *
 * @Flow\Entity
 */
class DynamicVipsAdjustment extends AbstractImageAdjustment
{
    /**
     * @var integer
     */
    protected $position = 10;

    /**
     * @var array
     */
    protected $rules;

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * Check if this Adjustment can or should be applied to its ImageVariant.
     *
     * @param ImagineImageInterface $image
     * @return bool
     */
    public function canBeApplied(ImagineImageInterface $image): bool
    {
        return true;
    }

    public function applyToImage(ImagineImageInterface $image): ImagineImageInterface
    {
        if ($image instanceof \Imagine\Vips\Image) {
            try {
                $image->applyToLayers(function (VipsImage $vips): VipsImage {

                    foreach ($this->rules as $ruleSet) {
                        foreach ($ruleSet as $ruleKey => $ruleArguments) {
                            list($methodName) = explode('#', $ruleKey); // everything up to the first "#" is the method name, the rest is discarded.
                            $vips = call_user_func_array([$vips, $methodName], $ruleArguments);
                        }
                    }

                    return $vips;
                });
            } catch (Exception $e) {
                throw new RuntimeException('Failed to apply gamma correction to the image', $e->getCode(), $e);
            }
        }

        return $image;
    }
}
