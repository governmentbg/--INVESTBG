(function() {
    'use strict';
    // Inject SVG icons into the DOM
    var element = document.createElement('div');
    element.id = 'darkroom-icons';
    element.style.height = 0;
    element.style.width = 0;
    element.style.position = 'absolute';
    element.style.visibility = 'hidden';
    element.innerHTML = '<!-- inject:svg --><svg xmlns="http://www.w3.org/2000/svg"><symbol id="thumbnail" viewBox="0 0 1920 1792"><path d="M640 576q0 80-56 136t-136 56-136-56-56-136 56-136 136-56 136 56 56 136zm1024 384v448h-1408v-192l320-320 160 160 512-512zm96-704h-1600q-13 0-22.5 9.5t-9.5 22.5v1216q0 13 9.5 22.5t22.5 9.5h1600q13 0 22.5-9.5t9.5-22.5v-1216q0-13-9.5-22.5t-22.5-9.5zm160 32v1216q0 66-47 113t-113 47h-1600q-66 0-113-47t-47-113v-1216q0-66 47-113t113-47h1600q66 0 113 47t47 113z"/></symbol><symbol id="close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/><path d="M0 0h24v24H0z" fill="none"/></symbol><symbol id="crop" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M17 15h2V7c0-1.1-.9-2-2-2H9v2h8v8zM7 17V1H5v4H1v2h4v10c0 1.1.9 2 2 2h10v4h2v-4h4v-2H7z"/></symbol><symbol id="done" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></symbol><symbol id="redo" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.9 16c1.05-3.19 4.05-5.5 7.6-5.5 1.95 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z"/></symbol><symbol id="rotate-left" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M7.11 8.53L5.7 7.11C4.8 8.27 4.24 9.61 4.07 11h2.02c.14-.87.49-1.72 1.02-2.47zM6.09 13H4.07c.17 1.39.72 2.73 1.62 3.89l1.41-1.42c-.52-.75-.87-1.59-1.01-2.47zm1.01 5.32c1.16.9 2.51 1.44 3.9 1.61V17.9c-.87-.15-1.71-.49-2.46-1.03L7.1 18.32zM13 4.07V1L8.45 5.55 13 10V6.09c2.84.48 5 2.94 5 5.91s-2.16 5.43-5 5.91v2.02c3.95-.49 7-3.85 7-7.93s-3.05-7.44-7-7.93z"/></symbol><symbol id="rotate-right" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M15.55 5.55L11 1v3.07C7.06 4.56 4 7.92 4 12s3.05 7.44 7 7.93v-2.02c-2.84-.48-5-2.94-5-5.91s2.16-5.43 5-5.91V10l4.55-4.45zM19.93 11c-.17-1.39-.72-2.73-1.62-3.89l-1.42 1.42c.54.75.88 1.6 1.02 2.47h2.02zM13 17.9v2.02c1.39-.17 2.74-.71 3.9-1.61l-1.44-1.44c-.75.54-1.59.89-2.46 1.03zm3.89-2.42l1.42 1.41c.9-1.16 1.45-2.5 1.62-3.89h-2.02c-.14.87-.48 1.72-1.02 2.48z"/></symbol><symbol id="save" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></symbol><symbol id="undo" viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z"/></symbol></svg><!-- endinject -->';
    document.body.appendChild(element);
})();
(function() {
    'use strict';

    window.Darkroom = Darkroom;

    // Core object of DarkroomJS.
    // Basically it's a single object, instanciable via an element
    // (it could be a CSS selector or a DOM element), some custom options,
    // and a list of plugin objects (or none to use default ones).
    function Darkroom(element, options, plugins) {
      return this.constructor(element, options, plugins);
    }

    // Create an empty list of plugin objects, which will be filled by
    // other plugin scripts. This is the default plugin list if none is
    // specified in Darkroom'ss constructor.
    Darkroom.plugins = [];

    Darkroom.prototype = {
      // Reference to the main container element
      containerElement: null,

      // Reference to the Fabric canvas object
      canvas: null,

      // Reference to the Fabric image object
      image: null,

      // Reference to the Fabric source canvas object
      sourceCanvas: null,

      // Reference to the Fabric source image object
      sourceImage: null,

      // Track of the original image element
      originalImageElement: null,

      // Stack of transformations to apply to the image source
      transformations: [],

      // Default options
      defaults: {
        // Canvas properties (dimension, ratio, color)
        minWidth: null,
        minHeight: null,
        maxWidth: null,
        maxHeight: null,
        ratio: null,
        backgroundColor: '#fff',

        // Plugins options
        plugins: {},

        // Post-initialisation callback
        initialize: function() { /* noop */ }
      },

      // List of the instancied plugins
      plugins: {},

      // This options are a merge between `defaults` and the options passed
      // through the constructor
      options: {},

      constructor: function(element, options, plugins) {
        this.options = Darkroom.Utils.extend(options, this.defaults);

        if (typeof element === 'string')
          element = document.querySelector(element);
        if (null === element)
          return;

        var image = new Image();
        image.onload = function() {
          // Initialize the DOM/Fabric elements
          this._initializeDOM(element);
          this._initializeImage();

          // Then initialize the plugins
          this._initializePlugins(Darkroom.plugins);

          // Public method to adjust image according to the canvas
          this.refresh(function() {
            // Execute a custom callback after initialization
            this.options.initialize.bind(this).call();
          }.bind(this));

        }.bind(this)

        //image.crossOrigin = 'anonymous';
        image.src = element.src;
      },

      selfDestroy: function() {
        var container = this.containerElement;
        var image = new Image();
        image.onload = function() {
          if (container && container.parentNode) {
            container.parentNode.replaceChild(image, container);
          }
          image.onload = function () { };
        }

        image.src = this.sourceImage.toDataURL();
      },

      // Add ability to attach event listener on the core object.
      // It uses the canvas element to process events.
      addEventListener: function(eventName, callback) {
        var el = this.canvas.getElement();
        if (el.addEventListener){
          el.addEventListener(eventName, callback);
        } else if (el.attachEvent) {
          el.attachEvent('on' + eventName, callback);
        }
      },

      dispatchEvent: function(eventName) {
        // Use the old way of creating event to be IE compatible
        // See https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
        var event = document.createEvent('Event');
        event.initEvent(eventName, true, true);

        this.canvas.getElement().dispatchEvent(event);
      },

      // Adjust image & canvas dimension according to min/max width/height
      // and ratio specified in the options.
      // This method should be called after each image transformation.
      refresh: function(next) {
        var clone = new Image();
        clone.onload = function() {
          this._replaceCurrentImage(new fabric.Image(clone));

          if (next) next();
        }.bind(this);
        clone.src = this.sourceImage.toDataURL();
      },

      _replaceCurrentImage: function(newImage) {
        if (this.image) {
          this.canvas.remove(this.image);
        }

        this.image = newImage;
        this.image.selectable = false;

        // Adjust width or height according to specified ratio
        var viewport = Darkroom.Utils.computeImageViewPort(this.image);
        var canvasWidth = viewport.width;
        var canvasHeight = viewport.height;

        if (null !== this.options.ratio) {
          var canvasRatio = +this.options.ratio;
          var currentRatio = canvasWidth / canvasHeight;

          if (currentRatio > canvasRatio) {
            canvasHeight = canvasWidth / canvasRatio;
          } else if (currentRatio < canvasRatio) {
            canvasWidth = canvasHeight * canvasRatio;
          }
        }

        // Then scale the image to fit into dimension limits
        var scaleMin = 1;
        var scaleMax = 1;
        var scaleX = 1;
        var scaleY = 1;

        if (null !== this.options.maxWidth && this.options.maxWidth < canvasWidth) {
          scaleX =  this.options.maxWidth / canvasWidth;
        }
        if (null !== this.options.maxHeight && this.options.maxHeight < canvasHeight) {
          scaleY =  this.options.maxHeight / canvasHeight;
        }
        scaleMin = Math.min(scaleX, scaleY);

        scaleX = 1;
        scaleY = 1;
        if (null !== this.options.minWidth && this.options.minWidth > canvasWidth) {
          scaleX =  this.options.minWidth / canvasWidth;
        }
        if (null !== this.options.minHeight && this.options.minHeight > canvasHeight) {
          scaleY =  this.options.minHeight / canvasHeight;
        }
        scaleMax = Math.max(scaleX, scaleY);

        var scale = scaleMax * scaleMin; // one should be equals to 1

        canvasWidth *= scale;
        canvasHeight *= scale;

        // Finally place the image in the center of the canvas
        this.image.scaleX = (1 * scale);
        this.image.scaleY = (1 * scale);
        this.canvas.add(this.image);
        this.canvas.setWidth(canvasWidth);
        this.canvas.setHeight(canvasHeight);
        this.canvas.centerObject(this.image);
        this.image.setCoords();
      },

      // Apply the transformation on the current image and save it in the
      // transformations stack (in order to reconstitute the previous states
      // of the image).
      applyTransformation: function(transformation) {
        this.transformations.push(transformation);

        transformation.applyTransformation(
          this.sourceCanvas,
          this.sourceImage,
          this._postTransformation.bind(this)
        );
      },

      _postTransformation: function(newImage) {
        if (newImage)
          this.sourceImage = newImage;

        this.refresh(function() {
          this.dispatchEvent('core:transformation');
        }.bind(this));
      },

      // Initialize image from original element plus re-apply every
      // transformations.
      reinitializeImage: function() {
        this.sourceCanvas.remove(this.sourceImage);
        this._initializeImage();
        this._popTransformation(this.transformations.slice())
      },

      _popTransformation: function(transformations) {
        if (0 === transformations.length) {
          this.dispatchEvent('core:reinitialized');
          this.refresh();
          return;
        }

        var transformation = transformations.shift();

        var next = function(newImage) {
          if (newImage) this.sourceImage = newImage;
          this._popTransformation(transformations)
        };

        transformation.applyTransformation(
          this.sourceCanvas,
          this.sourceImage,
          next.bind(this)
        );
      },

      // Create the DOM elements and instanciate the Fabric canvas.
      // The image element is replaced by a new `div` element.
      // However the original image is re-injected in order to keep a trace of it.
      _initializeDOM: function(imageElement) {
        // Container
        var mainContainerElement = document.createElement('div');
        mainContainerElement.className = 'darkroom-container';

        // Toolbar
        var toolbarElement = document.createElement('div');
        toolbarElement.className = 'darkroom-toolbar';
        mainContainerElement.appendChild(toolbarElement);

        // Viewport canvas
        var canvasContainerElement = document.createElement('div');
        canvasContainerElement.className = 'darkroom-image-container';
        var canvasElement = document.createElement('canvas');
        canvasContainerElement.appendChild(canvasElement);
        mainContainerElement.appendChild(canvasContainerElement);

        // Source canvas
        var sourceCanvasContainerElement = document.createElement('div');
        sourceCanvasContainerElement.className = 'darkroom-source-container';
        sourceCanvasContainerElement.style.display = 'none';
        var sourceCanvasElement = document.createElement('canvas');
        sourceCanvasContainerElement.appendChild(sourceCanvasElement);
        mainContainerElement.appendChild(sourceCanvasContainerElement);

        // Original image
        imageElement.parentNode.replaceChild(mainContainerElement, imageElement);
        imageElement.style.display = 'none';
        mainContainerElement.appendChild(imageElement);

        // Instanciate object from elements
        this.containerElement = mainContainerElement;
        this.originalImageElement = imageElement;

        this.toolbar = new Darkroom.UI.Toolbar(toolbarElement);

        this.canvas = new fabric.Canvas(canvasElement, {
          selection: false,
          backgroundColor: this.options.backgroundColor,
          enableRetinaScaling: false
        });

        this.sourceCanvas = new fabric.Canvas(sourceCanvasElement, {
          selection: false,
          backgroundColor: this.options.backgroundColor,
          enableRetinaScaling: false
        });
      },

      // Instanciate the Fabric image object.
      // The image is created as a static element with no control,
      // then it is add in the Fabric canvas object.
      _initializeImage: function() {
        this.sourceImage = new fabric.Image(this.originalImageElement, {
          // Some options to make the image static
          selectable: false,
          evented: false,
          lockMovementX: true,
          lockMovementY: true,
          lockRotation: true,
          lockScalingX: true,
          lockScalingY: true,
          lockUniScaling: true,
          hasControls: false,
          hasBorders: false,
        });

        this.sourceCanvas.add(this.sourceImage);

        // Adjust width or height according to specified ratio
        var viewport = Darkroom.Utils.computeImageViewPort(this.sourceImage);
        var canvasWidth = viewport.width;
        var canvasHeight = viewport.height;

        this.sourceCanvas.setWidth(canvasWidth);
        this.sourceCanvas.setHeight(canvasHeight);
        this.sourceCanvas.centerObject(this.sourceImage);
        this.sourceImage.setCoords();
      },

      // Initialize every plugins.
      // Note that plugins are instanciated in the same order than they
      // are declared in the parameter object.
      _initializePlugins: function(plugins) {
        for (var name in plugins) {
          var plugin = plugins[name];
          var options = this.options.plugins[name];

          // Setting false into the plugin options will disable the plugin
          if (options === false)
            continue;

          // Avoid any issues with _proto_
          if (!plugins.hasOwnProperty(name))
            continue;

          this.plugins[name] = new plugin(this, options);
        }
      },
    };
})();
(function() {
    'use strict';

    Darkroom.Plugin = Plugin;

    // Define a plugin object. This is the (abstract) parent class which
    // has to be extended for each plugin.
    function Plugin(darkroom, options) {
      this.darkroom = darkroom;
      this.options = Darkroom.Utils.extend(options, this.defaults);
      this.initialize();
    }

    Plugin.prototype = {
      defaults: {},
      initialize: function() { }
    }

    // Inspired by Backbone.js extend capability.
    Plugin.extend = function(protoProps) {
      var parent = this;
      var child;

      if (protoProps && protoProps.hasOwnProperty('constructor')) {
        child = protoProps.constructor;
      } else {
        child = function(){ return parent.apply(this, arguments); };
      }

      Darkroom.Utils.extend(child, parent);

      var Surrogate = function(){ this.constructor = child; };
      Surrogate.prototype = parent.prototype;
      child.prototype = new Surrogate;

      if (protoProps) Darkroom.Utils.extend(child.prototype, protoProps);

      child.__super__ = parent.prototype;

      return child;
    };
})();
(function() {
    'use strict';

    Darkroom.Transformation = Transformation;

    function Transformation(options) {
      this.options = options;
    }

    Transformation.prototype = {
      applyTransformation: function(image) { /* no-op */  }
    }

    // Inspired by Backbone.js extend capability.
    Transformation.extend = function(protoProps) {
      var parent = this;
      var child;

      if (protoProps && protoProps.hasOwnProperty('constructor')) {
        child = protoProps.constructor;
      } else {
        child = function(){ return parent.apply(this, arguments); };
      }

      Darkroom.Utils.extend(child, parent);

      var Surrogate = function(){ this.constructor = child; };
      Surrogate.prototype = parent.prototype;
      child.prototype = new Surrogate();

      if (protoProps) Darkroom.Utils.extend(child.prototype, protoProps);

      child.__super__ = parent.prototype;

      return child;
    };
})();
(function() {
    'use strict';

    Darkroom.UI = {
      Toolbar: Toolbar,
      ButtonGroup: ButtonGroup,
      Button: Button,
    };

    // Toolbar object.
    function Toolbar(element) {
      this.element = element;
    }

    Toolbar.prototype = {
      createButtonGroup: function(options) {
        var buttonGroup = document.createElement('div');
        buttonGroup.className = 'darkroom-button-group';
        this.element.appendChild(buttonGroup);

        return new ButtonGroup(buttonGroup);
      }
    };

    // ButtonGroup object.
    function ButtonGroup(element) {
      this.element = element;
    }

    ButtonGroup.prototype = {
      createButton: function(options) {
        var defaults = {
          image: 'help',
          type: 'default',
          group: 'default',
          hide: false,
          disabled: false
        };

        options = Darkroom.Utils.extend(options, defaults);

        var buttonElement = document.createElement('button');
        buttonElement.type = 'button';
        buttonElement.className = 'darkroom-button darkroom-button-' + options.type;
        buttonElement.innerHTML = '<svg class="darkroom-icon"><use xlink:href="#' + options.image + '" /></svg>';
        this.element.appendChild(buttonElement);

        var button = new Button(buttonElement);
        button.hide(options.hide);
        button.disable(options.disabled);

        return button;
      }
    }

    // Button object.
    function Button(element) {
      this.element = element;
    }

    Button.prototype = {
      addEventListener: function(eventName, listener) {
        if (this.element.addEventListener){
          this.element.addEventListener(eventName, listener);
        } else if (this.element.attachEvent) {
          this.element.attachEvent('on' + eventName, listener);
        }
      },
      removeEventListener: function(eventName, listener) {
        if (this.element.removeEventListener){
          this.element.removeEventListener(eventName, listener);
        }
      },
      active: function(value) {
        if (value)
          this.element.classList.add('darkroom-button-active');
        else
          this.element.classList.remove('darkroom-button-active');
      },
      hide: function(value) {
        if (value)
          this.element.classList.add('darkroom-button-hidden');
        else
          this.element.classList.remove('darkroom-button-hidden');
      },
      disable: function(value) {
        this.element.disabled = (value) ? true : false;
      }
    };
})();
(function() {
    'use strict';

    Darkroom.Utils = {
      extend: extend,
      computeImageViewPort: computeImageViewPort,
    };


    // Utility method to easily extend objects.
    function extend(b, a) {
      var prop;
      if (b === undefined) {
        return a;
      }
      for (prop in a) {
        if (a.hasOwnProperty(prop) && b.hasOwnProperty(prop) === false) {
          b[prop] = a[prop];
        }
      }
      return b;
    }

    function computeImageViewPort(image) {
      return {
        height: Math.abs(image.getScaledWidth() * (Math.sin(image.angle * Math.PI/180))) + Math.abs(image.getScaledHeight() * (Math.cos(image.angle * Math.PI/180))),
        width: Math.abs(image.getScaledHeight() * (Math.sin(image.angle * Math.PI/180))) + Math.abs(image.getScaledWidth() * (Math.cos(image.angle * Math.PI/180))),
      }
    }
})();
(function(window, document, Darkroom, fabric) {
  'use strict';

  Darkroom.plugins['history'] = Darkroom.Plugin.extend({
    undoTransformations: [],

    initialize: function InitDarkroomHistoryPlugin() {
      this._initButtons();

      this.darkroom.addEventListener('core:transformation', this._onTranformationApplied.bind(this));
    },

    undo: function() {
      if (this.darkroom.transformations.length === 0) {
        return;
      }

      var lastTransformation = this.darkroom.transformations.pop();
      this.undoTransformations.unshift(lastTransformation);

      this.darkroom.reinitializeImage();
      this._updateButtons();
    },

    redo: function() {
      if (this.undoTransformations.length === 0) {
        return;
      }

      var cancelTransformation = this.undoTransformations.shift();
      this.darkroom.transformations.push(cancelTransformation);

      this.darkroom.reinitializeImage();
      this._updateButtons();
    },

    _initButtons: function() {
      var buttonGroup = this.darkroom.toolbar.createButtonGroup();

      this.backButton = buttonGroup.createButton({
        image: 'undo',
        disabled: true
      });

      this.forwardButton = buttonGroup.createButton({
        image: 'redo',
        disabled: true
      });

      this.backButton.addEventListener('click', this.undo.bind(this));
      this.forwardButton.addEventListener('click', this.redo.bind(this));

      return this;
    },

    _updateButtons: function() {
      this.backButton.disable((this.darkroom.transformations.length === 0))
      this.forwardButton.disable((this.undoTransformations.length === 0))
    },

    _onTranformationApplied: function() {
      this.undoTransformations = [];
      this._updateButtons();
    }
  });
})(window, document, Darkroom, fabric);
(function() {
    'use strict';

    var Rotation = Darkroom.Transformation.extend({
      applyTransformation: function(canvas, image, next) {
        var angle = (image.angle + this.options.angle) % 360;
        image.rotate(angle);

        var width, height;
        height = Math.abs(image.getScaledWidth()*(Math.sin(angle*Math.PI/180)))+Math.abs(image.getScaledHeight()*(Math.cos(angle*Math.PI/180)));
        width = Math.abs(image.getScaledHeight()*(Math.sin(angle*Math.PI/180)))+Math.abs(image.getScaledWidth()*(Math.cos(angle*Math.PI/180)));

        canvas.setWidth(width);
        canvas.setHeight(height);

        canvas.centerObject(image);
        image.setCoords();
        canvas.renderAll();

        next();
      }
    });

    Darkroom.plugins['rotate'] = Darkroom.Plugin.extend({

      initialize: function InitDarkroomRotatePlugin() {
        var buttonGroup = this.darkroom.toolbar.createButtonGroup();

        var leftButton = buttonGroup.createButton({
          image: 'rotate-left'
        });

        var rightButton = buttonGroup.createButton({
          image: 'rotate-right'
        });

        leftButton.addEventListener('click', this.rotateLeft.bind(this));
        rightButton.addEventListener('click', this.rotateRight.bind(this));
      },

      rotateLeft: function rotateLeft() {
        this.rotate(-90);
      },

      rotateRight: function rotateRight() {
        this.rotate(90);
      },

      rotate: function rotate(angle) {
        this.darkroom.applyTransformation(
          new Rotation({angle: angle})
        );
      }

    });
})();

(function() {
    'use strict';

    var Crop = Darkroom.Transformation.extend({
      applyTransformation: function(canvas, image, next) {
        // Snapshot the image delimited by the crop zone
        var snapshot = new Image();
        snapshot.onload = function() {
          // Validate image
          if (height < 1 || width < 1)
            return;

          var imgInstance = new fabric.Image(this, {
            // options to make the image static
            selectable: false,
            evented: false,
            lockMovementX: true,
            lockMovementY: true,
            lockRotation: true,
            lockScalingX: true,
            lockScalingY: true,
            lockUniScaling: true,
            hasControls: false,
            hasBorders: false
          });

          var width = this.width;
          var height = this.height;

          // Update canvas size
          canvas.setWidth(width);
          canvas.setHeight(height);

          // Add image
          canvas.remove(image);
          canvas.add(imgInstance);

          next(imgInstance);
        };

        var viewport = Darkroom.Utils.computeImageViewPort(image);
        var imageWidth = viewport.width;
        var imageHeight = viewport.height;

        var left = this.options.left * imageWidth;
        var top = this.options.top * imageHeight;
        var width = Math.min(this.options.width * imageWidth, imageWidth - left);
        var height = Math.min(this.options.height * imageHeight, imageHeight - top);

        snapshot.src = canvas.toDataURL({
          left: left,
          top: top,
          width: width,
          height: height,
        });
      }
    });

    var CropZone = fabric.util.createClass(fabric.Rect, {
      render: function(ctx) {
        this.cornerStyle = 'circle';
        this.hoverCursor = 'move';
        this.cornerColor = 'rgba(255, 255, 255, 1)';
        this.callSuper('render', ctx);

        var canvas = ctx.canvas;
        var dashWidth = 7;

        // Set original scale
        var flipX = this.flipX ? -1 : 1;
        var flipY = this.flipY ? -1 : 1;
        var scaleX = flipX / this.scaleX;
        var scaleY = flipY / this.scaleY;

        ctx.scale(scaleX, scaleY);

        // Overlay rendering
        ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
        this._renderOverlay(ctx);

        // Set dashed borders
        // if (ctx.setLineDash !== undefined)
        //   ctx.setLineDash([dashWidth, dashWidth]);
        // else if (ctx.mozDash !== undefined)
        //   ctx.mozDash = [dashWidth, dashWidth];

        // First lines rendering with black
        // ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
        // this._renderBorders(ctx);
        // this._renderGrid(ctx);

        // Re render lines in white
        //ctx.lineDashOffset = dashWidth;
        ctx.strokeWidth = 1;
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.5)';
        this._renderBorders(ctx);
        this._renderGrid(ctx);

        // Reset scale
        ctx.scale(1/scaleX, 1/scaleY);
      },

      _renderOverlay: function(ctx) {
        var canvas = ctx.canvas;

        //
        //    x0    x1        x2      x3
        // y0 +------------------------+
        //    |\\\\\\\\\\\\\\\\\\\\\\\\|
        //    |\\\\\\\\\\\\\\\\\\\\\\\\|
        // y1 +------+---------+-------+
        //    |\\\\\\|         |\\\\\\\|
        //    |\\\\\\|    0    |\\\\\\\|
        //    |\\\\\\|         |\\\\\\\|
        // y2 +------+---------+-------+
        //    |\\\\\\\\\\\\\\\\\\\\\\\\|
        //    |\\\\\\\\\\\\\\\\\\\\\\\\|
        // y3 +------------------------+
        //

        var x0 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(-this.getScaledWidth() / 2 - this.left));
        var x1 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(-this.getScaledWidth() / 2));
        var x2 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(this.getScaledWidth() / 2));
        var x3 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(this.getScaledWidth() / 2 + (canvas.width - this.getScaledWidth() - this.left)));

        var y0 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(-this.getScaledHeight() / 2 - this.top));
        var y1 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(-this.getScaledHeight() / 2));
        var y2 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(this.getScaledHeight() / 2));
        var y3 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(this.getScaledHeight() / 2 + (canvas.height - this.getScaledHeight() - this.top)));

        ctx.beginPath();
        
        // Draw outer rectangle.
        // Numbers are +/-1 so that overlay edges don't get blurry.
        ctx.moveTo(x0 - 1, y0 - 1);
        ctx.lineTo(x3 + 1, y0 - 1);
        ctx.lineTo(x3 + 1, y3 + 1);
        ctx.lineTo(x0 - 1, y3 - 1);
        ctx.lineTo(x0 - 1, y0 - 1);
        ctx.closePath();

        // Draw inner rectangle.
        ctx.moveTo(x1, y1);
        ctx.lineTo(x1, y2);
        ctx.lineTo(x2, y2);
        ctx.lineTo(x2, y1);
        ctx.lineTo(x1, y1);

        ctx.closePath();
        ctx.fill();
      },

      _renderBorders: function(ctx) {
        ctx.beginPath();
        ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2))); // upper left
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2))); // upper right
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2))); // down right
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2))); // down left
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2))); // upper left
        ctx.stroke();
      },

      _renderGrid: function(ctx) {
        // Vertical lines
        ctx.beginPath();
        ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 1/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2)));
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 1/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2)));
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 2/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2)));
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 2/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2)));
        ctx.stroke();
        // Horizontal lines
        ctx.beginPath();
        ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 1/3 * this.getScaledHeight())));
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 1/3 * this.getScaledHeight())));
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 2/3 * this.getScaledHeight())));
        ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 2/3 * this.getScaledHeight())));
        ctx.stroke();
      }
    });

    Darkroom.plugins['crop'] = Darkroom.Plugin.extend({
      // Init point
      startX: null,
      startY: null,

      // Keycrop
      isKeyCroping: false,
      isKeyLeft: false,
      isKeyUp: false,

      defaults: {
        // min crop dimension
        minHeight: 1,
        minWidth: 1,
        // ensure crop ratio
        ratio: null,
        // quick crop feature (set a key code to enable it)
        quickCropKey: false
      },

      initialize: function InitDarkroomCropPlugin() {
        var buttonGroup = this.darkroom.toolbar.createButtonGroup();

        this.cropButton = buttonGroup.createButton({
          image: 'crop'
        });
        this.okButton = buttonGroup.createButton({
          image: 'done',
          type: 'success',
          hide: true
        });
        this.cancelButton = buttonGroup.createButton({
          image: 'close',
          type: 'danger',
          hide: true
        });

        // Buttons click
        this.cropButton.addEventListener('click', this.toggleCrop.bind(this));
        this.okButton.addEventListener('click', this.cropCurrentZone.bind(this));
        this.cancelButton.addEventListener('click', this.releaseFocus.bind(this));

        // Canvas events
        this.darkroom.canvas.on('mouse:down', this.onMouseDown.bind(this));
        this.darkroom.canvas.on('mouse:move', this.onMouseMove.bind(this));
        this.darkroom.canvas.on('mouse:up', this.onMouseUp.bind(this));
        this.darkroom.canvas.on('object:moving', this.onObjectMoving.bind(this));
        this.darkroom.canvas.on('object:scaling', this.onObjectScaling.bind(this));

        fabric.util.addListener(fabric.document, 'keydown', this.onKeyDown.bind(this));
        fabric.util.addListener(fabric.document, 'keyup', this.onKeyUp.bind(this));

        this.darkroom.addEventListener('core:transformation', this.releaseFocus.bind(this));
      },

      // Avoid crop zone to go beyond the canvas edges
      onObjectMoving: function(event) {
        if (!this.hasFocus()) {
          return;
        }

        var currentObject = event.target;
        if (currentObject !== this.cropZone)
          return;

        var canvas = this.darkroom.canvas;
        var x = currentObject.left, y = currentObject.top;
        var w = currentObject.getScaledWidth(), h = currentObject.getScaledHeight();
        var maxX = canvas.getWidth() - w;
        var maxY = canvas.getHeight() - h;

        if (x < 0)
          currentObject.set('left', 0);
        if (y < 0)
          currentObject.set('top', 0);
        if (x > maxX)
          currentObject.set('left', maxX);
        if (y > maxY)
          currentObject.set('top', maxY);

        this.darkroom.dispatchEvent('crop:update');
      },

      // Prevent crop zone from going beyond the canvas edges (like mouseMove)
      onObjectScaling: function(event) {
        if (!this.hasFocus()) {
          return;
        }

        var preventScaling = false;
        var currentObject = event.target;
        if (currentObject !== this.cropZone)
          return;

        var canvas = this.darkroom.canvas;
        var pointer = canvas.getPointer(event.e);
        var x = pointer.x;
        var y = pointer.y;

        var minX = currentObject.left;
        var minY = currentObject.top;
        var maxX = currentObject.left + currentObject.getScaledWidth();
        var maxY = currentObject.top + currentObject.getScaledHeight();

        if (null !== this.options.ratio) {
          if (minX < 0 || maxX > canvas.getWidth() || minY < 0 || maxY > canvas.getHeight()) {
            preventScaling = true;
          }
        }

        if (minX < 0 || maxX > canvas.getWidth() || preventScaling) {
          var lastScaleX = this.lastScaleX || 1;
          currentObject.scaleX = (lastScaleX);
        }
        if (minX < 0) {
          currentObject.left = (0);
        }

        if (minY < 0 || maxY > canvas.getHeight() || preventScaling) {
          var lastScaleY = this.lastScaleY || 1;
          currentObject.scaleY = (lastScaleY);
        }
        if (minY < 0) {
          currentObject.top = (0);
        }

        if (currentObject.getScaledWidth() < this.options.minWidth) {
          currentObject.scaleToWidth(this.options.minWidth);
        }
        if (currentObject.getScaledHeight() < this.options.minHeight) {
          currentObject.scaleToHeight(this.options.minHeight);
        }

        this.lastScaleX = currentObject.scaleX;
        this.lastScaleY = currentObject.scaleY;

        this.darkroom.dispatchEvent('crop:update');
      },

      // Init crop zone
      onMouseDown: function(event) {
        if (!this.hasFocus()) {
          return;
        }

        var canvas = this.darkroom.canvas;

        // recalculate offset, in case canvas was manipulated since last `calcOffset`
        canvas.calcOffset();
        var pointer = canvas.getPointer(event.e);
        var x = pointer.x;
        var y = pointer.y;
        var point = new fabric.Point(x, y);

        // Check if user want to scale or drag the crop zone.
        var activeObject = canvas.getActiveObject();
        if (activeObject === this.cropZone || this.cropZone.containsPoint(point)) {
          return;
        }

        canvas.discardActiveObject();
        this.cropZone.scaleToWidth(0);
        this.cropZone.scaleToHeight(0);
        this.cropZone.scaleX = (1);
        this.cropZone.scaleY = (1);

        this.startX = x;
        this.startY = y;
      },

      // Extend crop zone
      onMouseMove: function(event) {
        // Quick crop feature
        if (this.isKeyCroping)
          return this.onMouseMoveKeyCrop(event);

        if (null === this.startX || null === this.startY) {
          return;
        }

        var canvas = this.darkroom.canvas;
        var pointer = canvas.getPointer(event.e);
        var x = pointer.x;
        var y = pointer.y;

        this._renderCropZone(this.startX, this.startY, x, y);
      },

      onMouseMoveKeyCrop: function(event) {
        var canvas = this.darkroom.canvas;
        var zone = this.cropZone;

        var pointer = canvas.getPointer(event.e);
        var x = pointer.x;
        var y = pointer.y;

        if (!zone.left || !zone.top) {
          zone.top = (y);
          zone.left = (x);
        }

        this.isKeyLeft =  x < zone.left + zone.width / 2 ;
        this.isKeyUp = y < zone.top + zone.height / 2 ;

        this._renderCropZone(
          Math.min(zone.left, x),
          Math.min(zone.top, y),
          Math.max(zone.left+zone.width, x),
          Math.max(zone.top+zone.height, y)
        );
      },

      // Finish crop zone
      onMouseUp: function(event) {
        if (null === this.startX || null === this.startY) {
          return;
        }

        var canvas = this.darkroom.canvas;
        this.cropZone.setCoords();
        canvas.setActiveObject(this.cropZone);
        canvas.calcOffset();

        this.startX = null;
        this.startY = null;
      },

      onKeyDown: function(event) {
        if (false === this.options.quickCropKey || event.keyCode !== this.options.quickCropKey || this.isKeyCroping)
          return;

        // Active quick crop flow
        this.isKeyCroping = true ;
        this.darkroom.canvas.discardActiveObject();
        this.cropZone.setWidth(0);
        this.cropZone.setHeight(0);
        this.cropZone.scaleX = 1;
        this.cropZone.scaleY = 1;
        this.cropZone.setTop(0);
        this.cropZone.setLeft(0);
      },

      onKeyUp: function(event) {
        if (false === this.options.quickCropKey || event.keyCode !== this.options.quickCropKey || !this.isKeyCroping)
          return;

        // Unactive quick crop flow
        this.isKeyCroping = false;
        this.startX = 1;
        this.startY = 1;
        this.onMouseUp();
      },

      selectZone: function(x, y, width, height, forceDimension) {
        if (!this.hasFocus())
          this.requireFocus();

        if (!forceDimension) {
          this._renderCropZone(x, y, x+width, y+height);
        } else {
          this.cropZone.set({
            'left': x,
            'top': y,
            'width': width,
            'height': height
          });
        }

        var canvas = this.darkroom.canvas;
        canvas.bringToFront(this.cropZone);
        this.cropZone.setCoords();
        canvas.setActiveObject(this.cropZone);
        canvas.calcOffset();

        this.darkroom.dispatchEvent('crop:update');
      },

      toggleCrop: function() {
        if (!this.hasFocus())
          this.requireFocus();
        else
          this.releaseFocus();
      },

      cropCurrentZone: function() {
        if (!this.hasFocus())
          return;

        // Avoid croping empty zone
        if (this.cropZone.width < 1 && this.cropZone.height < 1)
          return;

        var image = this.darkroom.image;

        // Compute crop zone dimensions
        var top = this.cropZone.top - image.top;
        var left = this.cropZone.left - image.left;
        var width = this.cropZone.getScaledWidth();
        var height = this.cropZone.getScaledHeight();

        // Adjust dimensions to image only
        if (top < 0) {
          height += top;
          top = 0;
        }

        if (left < 0) {
          width += left;
          left = 0;
        }

        // Apply crop transformation.
        // Make sure to use relative dimension since the crop will be applied
        // on the source image.
        this.darkroom.applyTransformation(new Crop({
          top: top / image.getScaledHeight(),
          left: left / image.getScaledWidth(),
          width: width / image.getScaledWidth(),
          height: height / image.getScaledHeight(),
        }));
      },

      // Test wether crop zone is set
      hasFocus: function() {
        return this.cropZone !== undefined;
      },

      // Create the crop zone
      requireFocus: function() {
        this.cropZone = new CropZone({
          fill: 'transparent',
          hasBorders: false,
          originX: 'left',
          originY: 'top',
          //stroke: '#444',
          //strokeDashArray: [5, 5],
          //borderColor: '#444',
          cornerColor: '#444',
          cornerSize: 8,
          transparentCorners: false,
          lockRotation: true,
          hasRotatingPoint: false,
        });

        if (null !== this.options.ratio) {
          this.cropZone.set('lockUniScaling', true);
        }

        this.darkroom.canvas.add(this.cropZone);
        this.darkroom.canvas.hoverCursor = ('crosshair');

        this.cropButton.active(true);
        this.okButton.hide(false);
        this.cancelButton.hide(false);
      },

      // Remove the crop zone
      releaseFocus: function() {
        if (undefined === this.cropZone)
          return;

        this.darkroom.canvas.remove(this.cropZone);
        this.cropZone = undefined;

        this.cropButton.active(false);
        this.okButton.hide(true);
        this.cancelButton.hide(true);

        this.darkroom.canvas.hoverCursor=('default');

        this.darkroom.dispatchEvent('crop:update');
      },

      _renderCropZone: function(fromX, fromY, toX, toY) {
        var canvas = this.darkroom.canvas;

        var isRight = (toX > fromX);
        var isLeft = !isRight;
        var isDown = (toY > fromY);
        var isUp = !isDown;

        var minWidth = Math.min(+this.options.minWidth, canvas.getWidth());
        var minHeight = Math.min(+this.options.minHeight, canvas.getHeight());

        // Define corner coordinates
        var leftX = Math.min(fromX, toX);
        var rightX = Math.max(fromX, toX);
        var topY = Math.min(fromY, toY);
        var bottomY = Math.max(fromY, toY);

        // Replace current point into the canvas
        leftX = Math.max(0, leftX);
        rightX = Math.min(canvas.getWidth(), rightX);
        topY = Math.max(0, topY)
        bottomY = Math.min(canvas.getHeight(), bottomY);

        // Recalibrate coordinates according to given options
        if (rightX - leftX < minWidth) {
          if (isRight)
            rightX = leftX + minWidth;
          else
            leftX = rightX - minWidth;
        }
        if (bottomY - topY < minHeight) {
          if (isDown)
            bottomY = topY + minHeight;
          else
            topY = bottomY - minHeight;
        }

        // Truncate truncate according to canvas dimensions
        if (leftX < 0) {
          // Translate to the left
          rightX += Math.abs(leftX);
          leftX = 0
        }
        if (rightX > canvas.getWidth()) {
          // Translate to the right
          leftX -= (rightX - canvas.getWidth());
          rightX = canvas.getWidth();
        }
        if (topY < 0) {
          // Translate to the bottom
          bottomY += Math.abs(topY);
          topY = 0
        }
        if (bottomY > canvas.getHeight()) {
          // Translate to the right
          topY -= (bottomY - canvas.getHeight());
          bottomY = canvas.getHeight();
        }

        var width = rightX - leftX;
        var height = bottomY - topY;
        var currentRatio = width / height;

        if (this.options.ratio && +this.options.ratio !== currentRatio) {
          var ratio = +this.options.ratio;

          if(this.isKeyCroping) {
            isLeft = this.isKeyLeft;
            isUp = this.isKeyUp;
          }

          if (currentRatio < ratio) {
            var newWidth = height * ratio;
            if (isLeft) {
              leftX -= (newWidth - width);
            }
            width = newWidth;
          } else if (currentRatio > ratio) {
            var newHeight = height / (ratio * height/width);
            if (isUp) {
              topY -= (newHeight - height);
            }
            height = newHeight;
          }

          if (leftX < 0) {
            leftX = 0;
          }
          if (topY < 0) {
            topY = 0;
          }
          if (leftX + width > canvas.getWidth()) {
            var newWidth = canvas.getWidth() - leftX;
            height = newWidth * height / width;
            width = newWidth;
            if (isUp) {
              topY = fromY - height;
            }
          }
          if (topY + height > canvas.getHeight()) {
            var newHeight = canvas.getHeight() - topY;
            width = width * newHeight / height;
            height = newHeight;
            if (isLeft) {
              leftX = fromX - width;
            }
          }
        }

        // Apply coordinates
        this.cropZone.left = leftX;
        this.cropZone.top = topY;
        this.cropZone.width = width;
        this.cropZone.height = height;

        this.darkroom.canvas.bringToFront(this.cropZone);

        this.darkroom.dispatchEvent('crop:update');
      }
    });
})();

(function() {
  'use strict';

  var Crop = Darkroom.Transformation.extend({
    applyTransformation: function(canvas, image, next) {
      // Snapshot the image delimited by the crop zone
      var snapshot = new Image();
      snapshot.onload = function() {
        // Validate image
        if (height < 1 || width < 1)
          return;

        var imgInstance = new fabric.Image(this, {
          // options to make the image static
          selectable: false,
          evented: false,
          lockMovementX: true,
          lockMovementY: true,
          lockRotation: true,
          lockScalingX: true,
          lockScalingY: true,
          lockUniScaling: true,
          hasControls: false,
          hasBorders: false
        });

        var width = this.width;
        var height = this.height;

        // Update canvas size
        canvas.setWidth(width);
        canvas.setHeight(height);

        // Add image
        canvas.remove(image);
        canvas.add(imgInstance);

        next(imgInstance);
      };

      var viewport = Darkroom.Utils.computeImageViewPort(image);
      var imageWidth = viewport.width;
      var imageHeight = viewport.height;

      var left = this.options.left * imageWidth;
      var top = this.options.top * imageHeight;
      var width = Math.min(this.options.width * imageWidth, imageWidth - left);
      var height = Math.min(this.options.height * imageHeight, imageHeight - top);

      snapshot.src = canvas.toDataURL({
        left: left,
        top: top,
        width: width,
        height: height,
      });
    }
  });

  var CropZone = fabric.util.createClass(fabric.Rect, {
    render: function(ctx) {
      this.cornerStyle = 'circle';
      this.hoverCursor = 'move';
      this.cornerColor = 'rgba(255, 255, 255, 1)';
      this.callSuper('render', ctx);

      var canvas = ctx.canvas;
      var dashWidth = 7;

      // Set original scale
      var flipX = this.flipX ? -1 : 1;
      var flipY = this.flipY ? -1 : 1;
      var scaleX = flipX / this.scaleX;
      var scaleY = flipY / this.scaleY;

      ctx.scale(scaleX, scaleY);

      // Overlay rendering
      ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
      this._renderOverlay(ctx);

      // Set dashed borders
      // if (ctx.setLineDash !== undefined)
      //   ctx.setLineDash([dashWidth, dashWidth]);
      // else if (ctx.mozDash !== undefined)
      //   ctx.mozDash = [dashWidth, dashWidth];

      // First lines rendering with black
      // ctx.strokeStyle = 'rgba(0, 0, 0, 0.2)';
      // this._renderBorders(ctx);
      // this._renderGrid(ctx);

      // Re render lines in white
      //ctx.lineDashOffset = dashWidth;
      ctx.strokeWidth = 1;
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.5)';
      this._renderBorders(ctx);
      this._renderGrid(ctx);

      // Reset scale
      ctx.scale(1 / scaleX, 1 / scaleY);
    },

    _renderOverlay: function(ctx) {
      var canvas = ctx.canvas;

      //
      //    x0    x1        x2      x3
      // y0 +------------------------+
      //    |\\\\\\\\\\\\\\\\\\\\\\\\|
      //    |\\\\\\\\\\\\\\\\\\\\\\\\|
      // y1 +------+---------+-------+
      //    |\\\\\\|         |\\\\\\\|
      //    |\\\\\\|    0    |\\\\\\\|
      //    |\\\\\\|         |\\\\\\\|
      // y2 +------+---------+-------+
      //    |\\\\\\\\\\\\\\\\\\\\\\\\|
      //    |\\\\\\\\\\\\\\\\\\\\\\\\|
      // y3 +------------------------+
      //

      var x0 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(-this.getScaledWidth() / 2 - this.left));
      var x1 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(-this.getScaledWidth() / 2));
      var x2 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(this.getScaledWidth() / 2));
      var x3 = this.scaleX * (this.left + this.getScaledWidth() / 2 + Math.ceil(this.getScaledWidth() / 2 + (canvas.width - this.getScaledWidth() - this.left)));

      var y0 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(-this.getScaledHeight() / 2 - this.top));
      var y1 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(-this.getScaledHeight() / 2));
      var y2 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(this.getScaledHeight() / 2));
      var y3 = this.scaleY * (this.top + this.getScaledHeight() / 2 + Math.ceil(this.getScaledHeight() / 2 + (canvas.height - this.getScaledHeight() - this.top)));

      ctx.beginPath();
      
      // Draw outer rectangle.
      // Numbers are +/-1 so that overlay edges don't get blurry.
      ctx.moveTo(x0 - 1, y0 - 1);
      ctx.lineTo(x3 + 1, y0 - 1);
      ctx.lineTo(x3 + 1, y3 + 1);
      ctx.lineTo(x0 - 1, y3 - 1);
      ctx.lineTo(x0 - 1, y0 - 1);
      ctx.closePath();

      // Draw inner rectangle.
      ctx.moveTo(x1, y1);
      ctx.lineTo(x1, y2);
      ctx.lineTo(x2, y2);
      ctx.lineTo(x2, y1);
      ctx.lineTo(x1, y1);

      ctx.closePath();
      ctx.fill();
    },

    _renderBorders: function(ctx) {
      ctx.beginPath();
      ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2))); // upper left
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2))); // upper right
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2))); // down right
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2))); // down left
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2))); // upper left
      ctx.stroke();
    },

    _renderGrid: function(ctx) {
      // Vertical lines
      ctx.beginPath();
      ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 1/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2)));
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 1/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2)));
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 2/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2)));
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2 + 2/3 * this.getScaledWidth())), this.scaleY * (this.top + this.getScaledHeight() / 2 + (this.getScaledHeight()/2)));
      ctx.stroke();
      // Horizontal lines
      ctx.beginPath();
      ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 1/3 * this.getScaledHeight())));
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 1/3 * this.getScaledHeight())));
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (-this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 2/3 * this.getScaledHeight())));
      ctx.lineTo(this.scaleX * (this.left + this.getScaledWidth() / 2 + (this.getScaledWidth()/2)), this.scaleY * (this.top + this.getScaledHeight() / 2 + (-this.getScaledHeight()/2 + 2/3 * this.getScaledHeight())));
      ctx.stroke();
    }
  });

  Darkroom.plugins['thumbnail'] = Darkroom.Plugin.extend({
    // Init point
    startX: null,
    startY: null,

    // Keycrop
    isKeyCroping: false,
    isKeyLeft: false,
    isKeyUp: false,

    defaults: {
      // min crop dimension
      minHeight: 1,
      minWidth: 1,
      // ensure crop ratio
      ratio: null,
      // quick crop feature (set a key code to enable it)
      quickCropKey: false,
      x : null,
      y : null,
      w : null,
      h : null
    },

    initialize: function InitDarkroomCropPlugin() {
      var buttonGroup = this.darkroom.toolbar.createButtonGroup();

      this.cropButton = buttonGroup.createButton({
        image: 'thumbnail'
      });
      this.okButton = buttonGroup.createButton({
        image: 'done',
        type: 'success',
        hide: true
      });
      this.cancelButton = buttonGroup.createButton({
        image: 'close',
        type: 'danger',
        hide: true
      });

      // Buttons click
      this.cropButton.addEventListener('click', this.toggleCrop.bind(this));
      this.okButton.addEventListener('click', this.cropCurrentZone.bind(this));
      this.cancelButton.addEventListener('click', this.releaseFocus.bind(this));

      // Canvas events
      this.darkroom.canvas.on('mouse:down', this.onMouseDown.bind(this));
      this.darkroom.canvas.on('mouse:move', this.onMouseMove.bind(this));
      this.darkroom.canvas.on('mouse:up', this.onMouseUp.bind(this));
      this.darkroom.canvas.on('object:moving', this.onObjectMoving.bind(this));
      this.darkroom.canvas.on('object:scaling', this.onObjectScaling.bind(this));

      fabric.util.addListener(fabric.document, 'keydown', this.onKeyDown.bind(this));
      fabric.util.addListener(fabric.document, 'keyup', this.onKeyUp.bind(this));

      this.darkroom.addEventListener('core:transformation', this.releaseFocus.bind(this));
    },

    // Avoid crop zone to go beyond the canvas edges
    onObjectMoving: function(event) {
      if (!this.hasFocus()) {
        return;
      }

      var currentObject = event.target;
      if (currentObject !== this.cropZone)
        return;

      var canvas = this.darkroom.canvas;
        var x = currentObject.left, y = currentObject.top;
        var w = currentObject.getScaledWidth(), h = currentObject.getScaledHeight();
      var maxX = canvas.getWidth() - w;
      var maxY = canvas.getHeight() - h;

      if (x < 0)
        currentObject.set('left', 0);
      if (y < 0)
        currentObject.set('top', 0);
      if (x > maxX)
        currentObject.set('left', maxX);
      if (y > maxY)
        currentObject.set('top', maxY);

      this.darkroom.dispatchEvent('crop:update');
    },

    // Prevent crop zone from going beyond the canvas edges (like mouseMove)
    onObjectScaling: function(event) {
      if (!this.hasFocus()) {
        return;
      }

      var preventScaling = false;
      var currentObject = event.target;
      if (currentObject !== this.cropZone)
        return;

      var canvas = this.darkroom.canvas;
      var pointer = canvas.getPointer(event.e);
      var x = pointer.x;
      var y = pointer.y;

        var minX = currentObject.left;
        var minY = currentObject.top;
        var maxX = currentObject.left + currentObject.getScaledWidth();
        var maxY = currentObject.top + currentObject.getScaledHeight();

      if (null !== this.options.ratio) {
        if (minX < 0 || maxX > canvas.getWidth() || minY < 0 || maxY > canvas.getHeight()) {
          preventScaling = true;
        }
      }

      if (minX < 0 || maxX > canvas.getWidth() || preventScaling) {
        var lastScaleX = this.lastScaleX || 1;
          currentObject.scaleX = (lastScaleX);
      }
      if (minX < 0) {
          currentObject.left = (0);
      }

      if (minY < 0 || maxY > canvas.getHeight() || preventScaling) {
        var lastScaleY = this.lastScaleY || 1;
          currentObject.scaleY = (lastScaleY);
      }
      if (minY < 0) {
          currentObject.top = (0);
      }

        if (currentObject.getScaledWidth() < this.options.minWidth) {
        currentObject.scaleToWidth(this.options.minWidth);
      }
        if (currentObject.getScaledHeight() < this.options.minHeight) {
        currentObject.scaleToHeight(this.options.minHeight);
      }

        this.lastScaleX = currentObject.scaleX;
        this.lastScaleY = currentObject.scaleY;

      this.darkroom.dispatchEvent('crop:update');
    },

    // Init crop zone
    onMouseDown: function(event) {
      if (!this.hasFocus()) {
        return;
      }

      var canvas = this.darkroom.canvas;

      // recalculate offset, in case canvas was manipulated since last `calcOffset`
      canvas.calcOffset();
      var pointer = canvas.getPointer(event.e);
      var x = pointer.x;
      var y = pointer.y;
      var point = new fabric.Point(x, y);

      // Check if user want to scale or drag the crop zone.
      var activeObject = canvas.getActiveObject();
      if (activeObject === this.cropZone || this.cropZone.containsPoint(point)) {
        return;
      }

      canvas.discardActiveObject();
        this.cropZone.scaleToWidth(0);
        this.cropZone.scaleToHeight(0);
        this.cropZone.scaleX = (1);
        this.cropZone.scaleY = (1);

      this.startX = x;
      this.startY = y;
    },

    // Extend crop zone
    onMouseMove: function(event) {
      // Quick crop feature
      if (this.isKeyCroping)
        return this.onMouseMoveKeyCrop(event);

      if (null === this.startX || null === this.startY) {
        return;
      }

      var canvas = this.darkroom.canvas;
      var pointer = canvas.getPointer(event.e);
      var x = pointer.x;
      var y = pointer.y;

      this._renderCropZone(this.startX, this.startY, x, y);
    },

    onMouseMoveKeyCrop: function(event) {
      var canvas = this.darkroom.canvas;
      var zone = this.cropZone;

      var pointer = canvas.getPointer(event.e);
      var x = pointer.x;
      var y = pointer.y;

      if (!zone.left || !zone.top) {
          zone.top = (y);
          zone.left = (x);
      }

      this.isKeyLeft =  x < zone.left + zone.width / 2 ;
      this.isKeyUp = y < zone.top + zone.height / 2 ;

      this._renderCropZone(
        Math.min(zone.left, x),
        Math.min(zone.top, y),
        Math.max(zone.left+zone.width, x),
        Math.max(zone.top+zone.height, y)
      );
    },

    // Finish crop zone
    onMouseUp: function(event) {
      if (null === this.startX || null === this.startY) {
        return;
      }

      var canvas = this.darkroom.canvas;
      this.cropZone.setCoords();
      canvas.setActiveObject(this.cropZone);
      canvas.calcOffset();

      this.startX = null;
      this.startY = null;
    },

    onKeyDown: function(event) {
      if (false === this.options.quickCropKey || event.keyCode !== this.options.quickCropKey || this.isKeyCroping)
        return;

      // Active quick crop flow
      this.isKeyCroping = true ;
      this.darkroom.canvas.discardActiveObject();
      this.cropZone.setWidth(0);
      this.cropZone.setHeight(0);
        this.cropZone.scaleX = 1;
        this.cropZone.scaleY = 1;
      this.cropZone.setTop(0);
      this.cropZone.setLeft(0);
    },

    onKeyUp: function(event) {
      if (false === this.options.quickCropKey || event.keyCode !== this.options.quickCropKey || !this.isKeyCroping)
        return;

      // Unactive quick crop flow
      this.isKeyCroping = false;
      this.startX = 1;
      this.startY = 1;
      this.onMouseUp();
    },

    selectZone: function(x, y, width, height, forceDimension) {
      if (!this.hasFocus())
        this.requireFocus();

      if (!forceDimension) {
        this._renderCropZone(x, y, x+width, y+height);
      } else {
        this.cropZone.set({
          'left': x,
          'top': y,
          'width': width,
          'height': height
        });
      }

      var canvas = this.darkroom.canvas;
      canvas.bringToFront(this.cropZone);
      this.cropZone.setCoords();
      canvas.setActiveObject(this.cropZone);
      canvas.calcOffset();

      this.darkroom.dispatchEvent('crop:update');
    },

    toggleCrop: function() {
      if (!this.hasFocus()) {
        this.requireFocus();
        var image = this.darkroom.image;
        if (this.options.x === null || this.options.y === null || this.options.w === null || this.options.h === null) {
          this.options.w = Math.floor(image.getScaledWidth() / 2) / image.scaleX;
          this.options.h = Math.floor(image.getScaledHeight() / 2) / image.scaleY;
          this.options.x = Math.floor(image.getScaledWidth() / 4) / image.scaleX;
          this.options.y = Math.floor(image.getScaledHeight() / 4) / image.scaleY;
        }
        this.selectZone(this.options.x * image.scaleX, this.options.y * image.scaleY, this.options.w * image.scaleX, this.options.h * image.scaleY);
      } else {
        this.releaseFocus();
      }
    },

    cropCurrentZone: function() {
      if (!this.hasFocus())
        return;

      // Avoid croping empty zone
      if (this.cropZone.width < 1 && this.cropZone.height < 1)
        return;

      var image = this.darkroom.image;
      // Compute crop zone dimensions
      this.options.y = (this.cropZone.top - image.top) / image.scaleY;
      this.options.x = (this.cropZone.left - image.left) / image.scaleX;
      this.options.w = this.cropZone.getScaledWidth() / image.scaleX;
      this.options.h = this.cropZone.getScaledHeight() / image.scaleY;
      this.releaseFocus();
    },

    // Test wether crop zone is set
    hasFocus: function() {
      return this.cropZone !== undefined;
    },

    // Create the crop zone
    requireFocus: function() {
      this.cropZone = new CropZone({
        fill: 'transparent',
        hasBorders: false,
        originX: 'left',
        originY: 'top',
        //stroke: '#444',
        //strokeDashArray: [5, 5],
        //borderColor: '#444',
        cornerColor: '#444',
        cornerSize: 8,
        transparentCorners: false,
        lockRotation: true,
        hasRotatingPoint: false,
      });

      if (null !== this.options.ratio) {
        this.cropZone.set('lockUniScaling', true);
      }

      this.darkroom.canvas.add(this.cropZone);
      this.darkroom.canvas.hoverCursor = ('crosshair');

      this.cropButton.active(true);
      this.okButton.hide(false);
      this.cancelButton.hide(false);
    },

    // Remove the crop zone
    releaseFocus: function() {
      if (undefined === this.cropZone)
        return;

      this.darkroom.canvas.remove(this.cropZone);
      this.cropZone = undefined;

      this.cropButton.active(false);
      this.okButton.hide(true);
      this.cancelButton.hide(true);

      this.darkroom.canvas.hoverCursor = ('default');

      this.darkroom.dispatchEvent('crop:update');
    },

    _renderCropZone: function(fromX, fromY, toX, toY) {
      var canvas = this.darkroom.canvas;

      var isRight = (toX > fromX);
      var isLeft = !isRight;
      var isDown = (toY > fromY);
      var isUp = !isDown;

      var minWidth = Math.min(+this.options.minWidth, canvas.getWidth());
      var minHeight = Math.min(+this.options.minHeight, canvas.getHeight());

      // Define corner coordinates
      var leftX = Math.min(fromX, toX);
      var rightX = Math.max(fromX, toX);
      var topY = Math.min(fromY, toY);
      var bottomY = Math.max(fromY, toY);

      // Replace current point into the canvas
      leftX = Math.max(0, leftX);
      rightX = Math.min(canvas.getWidth(), rightX);
      topY = Math.max(0, topY)
      bottomY = Math.min(canvas.getHeight(), bottomY);

      // Recalibrate coordinates according to given options
      if (rightX - leftX < minWidth) {
        if (isRight)
          rightX = leftX + minWidth;
        else
          leftX = rightX - minWidth;
      }
      if (bottomY - topY < minHeight) {
        if (isDown)
          bottomY = topY + minHeight;
        else
          topY = bottomY - minHeight;
      }

      // Truncate truncate according to canvas dimensions
      if (leftX < 0) {
        // Translate to the left
        rightX += Math.abs(leftX);
        leftX = 0
      }
      if (rightX > canvas.getWidth()) {
        // Translate to the right
        leftX -= (rightX - canvas.getWidth());
        rightX = canvas.getWidth();
      }
      if (topY < 0) {
        // Translate to the bottom
        bottomY += Math.abs(topY);
        topY = 0
      }
      if (bottomY > canvas.getHeight()) {
        // Translate to the right
        topY -= (bottomY - canvas.getHeight());
        bottomY = canvas.getHeight();
      }

      var width = rightX - leftX;
      var height = bottomY - topY;
      var currentRatio = width / height;

      if (this.options.ratio && +this.options.ratio !== currentRatio) {
        var ratio = +this.options.ratio;

        if(this.isKeyCroping) {
          isLeft = this.isKeyLeft;
          isUp = this.isKeyUp;
        }

        if (currentRatio < ratio) {
          var newWidth = height * ratio;
          if (isLeft) {
            leftX -= (newWidth - width);
          }
          width = newWidth;
        } else if (currentRatio > ratio) {
          var newHeight = height / (ratio * height/width);
          if (isUp) {
            topY -= (newHeight - height);
          }
          height = newHeight;
        }

        if (leftX < 0) {
          leftX = 0;
        }
        if (topY < 0) {
          topY = 0;
        }
        if (leftX + width > canvas.getWidth()) {
          var newWidth = canvas.getWidth() - leftX;
          height = newWidth * height / width;
          width = newWidth;
          if (isUp) {
            topY = fromY - height;
          }
        }
        if (topY + height > canvas.getHeight()) {
          var newHeight = canvas.getHeight() - topY;
          width = width * newHeight / height;
          height = newHeight;
          if (isLeft) {
            leftX = fromX - width;
          }
        }
      }

      // Apply coordinates
      this.cropZone.left = leftX;
      this.cropZone.top = topY;
      this.cropZone.width = width;
      this.cropZone.height = height;

      this.darkroom.canvas.bringToFront(this.cropZone);

      this.darkroom.dispatchEvent('crop:update');
    }
  });

})();

