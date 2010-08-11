Ext.ns("F3.TYPO3.UserInterface");

/**
 * @class F3.TYPO3.UserInterface.BreadcrumbMenuButton
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.Button
 *
 * Button of the breadcrumb menu
 */
F3.TYPO3.UserInterface.BreadcrumbMenuButton = Ext.extend(Ext.Button, {
    enableToggle: true,

    initComponent: function() {
	var config = {
	    scale: 'large',
	    overCls: 'F3-TYPO3-UserInterface-BreadcrumbMenu-over'
	};
	Ext.apply(this, config);
	F3.TYPO3.UserInterface.BreadcrumbMenuButton.superclass.initComponent.call(this);

	this.enableBubble([
	    'F3.TYPO3.UserInterface.BreadcrumbMenu.buttonPressed',
	    'F3.TYPO3.UserInterface.BreadcrumbMenu.buttonUnpressed'
	]);

	this.on(
	    'toggle',
	    this.onToggleAction,
	    this
	    );

	this.on(
	    'mouseover',
	    this.onMouseoverAction,
	    this
	    );
    },

    /**
     * @method onToggleAction
     * @param {object} button
     * @param {bool} pressed
     * @return void
     */
    onToggleAction: function(button, pressed) {
	if (pressed) {
	    this._onButtonPress(button);
	} else {
	    this._onButtonUnpress(button);
	}
    },

    _onButtonPress: function(button) {
	button.ownerCt.items.each(function(item) {
	    if (button.leaf) {
		if (item.menuLevel === button.menuLevel && item !== button && item.itemId !== 'F3-arrow') {
		    item.el.fadeOut({
			duration: .4,
			endOpacity: .5
		    });
		}
	    } else {
		if (item.menuLevel === button.menuLevel && item !== button && item.itemId !== 'F3-arrow') {
		    item.el.fadeOut({
			duration: .4,
			callback: function() {
			    if (item.pressed) {
				item.toggle(false);
			    }
			    item.hide();
			}
		    });
		}
		if (item.menuPath.indexOf(button.menuPath + '-') === 0) {
		    item.el.fadeIn({
			duration: .4,
			callback: function() {
			    item.show();
			}
		    });
		}
	    }
	}, this);
	button.fireEvent('F3.TYPO3.UserInterface.BreadcrumbMenu.buttonPressed', this);
    },

    _onButtonUnpress: function(button) {
	button.ownerCt.items.each(function(item) {
	    if (button.leaf) {
		if (item.menuLevel === button.menuLevel && item !== button && item.itemId !== 'F3-arrow') {
		    item.el.fadeIn({
			duration: .4,
			startOpacity: .5
		    });
		}
	    } else {
		if (item.menuLevel === button.menuLevel && item !== button) {
		    item.el.fadeIn({
			duration: .4,
			callback: function() {
			    item.show();
			}
		    });
		}
		if (item.menuPath.indexOf(button.menuPath + '-') === 0) {
		    item.el.fadeOut({
			duration: .4,
			callback: function() {
			    if (item.pressed) {
				item.toggle(false);
			    }
			    item.hide();
			}
		    });
		}
	    }
	}, this);
	button.fireEvent('F3.TYPO3.UserInterface.BreadcrumbMenu.buttonUnpressed', this);
    },

    getFullPath: function() {
	return this.menuId + '-' + this.sectionId + '-' + this.menuPath;
    },

    /**
     * @method onMouseoverAction
     * @param {object} button
     * @param {object} event
     * @return void
     */
    onMouseoverAction: function(button, event) {
    }

});
Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenuButton', F3.TYPO3.UserInterface.BreadcrumbMenuButton);
