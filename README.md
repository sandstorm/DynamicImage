# Sandstorm.DynamicImage - dynamically modify images using VIPS

Dynamically modify Neos images using the VIPS operations.

With this package, you can:

- add **gaussian blur** to an image
- modify **Saturation/Hue/Lightness** on an image
- adjust anything on the image based on LibVIPS API

... all this is configured using Fusion objects. You can dynamically modify effects and effect properties; e.g.
based on Node properties etc.

With this package, we expose the low-level LibVIPS API directly in Fusion. This means we expose the full
power of VIPS, but you need to know the VIPS API to use it properly.


## Prerequisites

You need to use [VIPS as image processor](https://www.flownative.com/de/dokumentation/anleitungen/beach/neos-cms-vips-for-faster-image-rendering.html)
in Neos CMS. It will not work with GD or ImageMagick/GraphicsMagic!


## Example

The following example applies two filters to the image specified in `${q(node).property('image')}`: 

- First, a **Gaussian Blur** filter
- Second, change the **Vibrance** 

```
dynamicImage = Sandstorm.DynamicImage:VipsVariant {
    image = ${q(node).property('image')}

    // The identifier ensures we create the VipsVariant only once (per workspace) - so that's the Identity Property
    // of this ImageVariant.    
    identifier = "blur"

    rules {
        10 = Sandstorm.DynamicImage:Rule.GaussianBlur {
            // a radius of 2 px for Gaussian Blur
            sigma = 2
        }

        20 = Sandstorm.DynamicImage:Rule.LCH {
            // change the vibrance ("Sättigung") by multiplying the "chroma" channel of the LCH (Luminance, Chroma, Hue) image
            chroma = 2
        }
    }
}
```


## Usage

### Sandstorm.DynamicImage:VipsVariant

You always need to use a `Sandstorm.DynamicImage:VipsVariant` Fusion object which generates an `ImageVariant`
object at runtime:

```
dynamicImage = Sandstorm.DynamicImage:VipsVariant {
    image = ${q(node).property('image')}
    identifier = "MUST BE UNIQUE GLOBALLY!"
    rules {
        // ...
    }
}
```

**Properties**:

- **image** (Neos `Image` or `ImageVariant`, *required*): input image to use
- **identifier** (String, *required*): Identifier of the Image Variant. Must be globally unique in Fusion.
- **rules**: Nested VIPS rules.


### Identifier Generation

We do not want to generate a new `ImageVariant` for each `VipsVariant` invocation, as this would generate
new ImageVariants on every Fusion rendering.

To prevent this from happening, we deterministically generate the `ImageVariant` identifiers by taking the
base image's UUID, **combined with the identifier property of `Sandstorm.DynamicImage:VipsVariant`**.

Thus, during Fusion rendering, you need to specify a different `identifier` at each invocation (which can be
any string).


### Rule: GaussianBlur

The following example applies a Gaussian Blur filter to the image specified in `${q(node).property('image')}`.

```
dynamicImage = Sandstorm.DynamicImage:VipsVariant {
    image = ${q(node).property('image')}
    
    identifier = 'blur-example'
    rules {
        10 = Sandstorm.DynamicImage:Rule.GaussianBlur {
            sigma = 2
        }
    }
}
```

**Properties**:

- **sigma** (Float, default 1): how large a mask to use

vips reference: [*gaussblur*](https://libvips.github.io/libvips/API/current/libvips-convolution.html#vips-gaussblur)


### Rule: LCH

The following example changes the Chroma on the image specified in `${q(node).property('image')}`.

```
dynamicImage = Sandstorm.DynamicImage:VipsVariant {
    image = ${q(node).property('image')}
    
    identifier = 'lch-example'
    rules {
        20 = Sandstorm.DynamicImage:Rule.LCH {
            // change the vibrance ("Sättigung") by multiplying the "chroma" channel of the LCH (Luminance, Chroma, Hue) image
            chroma = 2
        }
    }
}
```

**Properties**:

- **luminance** (Float, default 1): multiplication factor for the Luminance channel ("Helligkeit")
- **chroma** (Float, default 1): multiplication factor for the Chroma channel ("Farbintensität")
- **hue** (Float, default 1): multiplication factor for the Hue channel ("Farbton")

vips reference: [*colour*](https://libvips.github.io/libvips/API/current/libvips-colour.html#libvips-colour.description) [*linear*](https://libvips.github.io/libvips/API/current/libvips-arithmetic.html#vips-linear)


### Combining Multiple Rules

Rules are executed sequentially (as shown in the 1st example above). 


### Custom Rules

A rule is a list of VIPS API calls which are executed sequentially. A rule is an associative array,
where the array key is the *VIPS Method Name*, and the value are the method arguments.

In case the same method call needs to be called multiple times as part of the same rule, you can
use array keys like `methodname# some arbitrary comment`, to make the array keys unique. This
is e.g. used in the `LCH` rule which contains two colorspace conversions.


## License

GPL (as it's based on the Neos.Media package)
