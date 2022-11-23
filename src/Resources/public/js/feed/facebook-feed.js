pimcore.registerNS('SocialData.Feed.Facebook');
SocialData.Feed.Facebook = Class.create(SocialData.Feed.AbstractFeed, {

    panel: null,

    getLayout: function () {

        this.panel = new Ext.form.FormPanel({
            title: false,
            defaults: {
                labelWidth: 200
            }
        });

        Ext.Ajax.request({
            url: '/admin/social-data/connector/facebook/feed-config',
            method: 'GET',
            success: function (response) {

                var res = Ext.decode(response.responseText);

                if (res.success !== true) {
                    Ext.MessageBox.alert(t('error'), res.message);
                    return;
                }

                this.panel.add(this.getConfigFields(res.data));

            }.bind(this)
        });

        return this.panel;
    },

    getConfigFields: function (feedConfig) {

        var fields = [];

        if (feedConfig.hasOwnProperty('pages')) {
            fields.push({
                xtype: 'combo',
                value: this.data !== null ? this.data['pageId'] : null,
                fieldLabel: t('social_data.wall.feed.facebook.page_id'),
                name: 'pageId',
                labelAlign: 'left',
                anchor: '100%',
                flex: 1,
                displayField: 'key',
                valueField: 'value',
                mode: 'local',
                triggerAction: 'all',
                queryDelay: 0,
                editable: false,
                summaryDisplay: true,
                allowBlank: false,
                store: new Ext.data.Store({
                    fields: ['value', 'key'],
                    data: feedConfig.pages
                })
            });
        } else {
            fields.push({
                xtype: 'textfield',
                value: this.data !== null ? this.data['pageId'] : null,
                fieldLabel: t('social_data.wall.feed.facebook.page_id'),
                name: 'pageId',
                labelAlign: 'left',
                anchor: '100%',
                flex: 1
            });
        }

        fields.push({
            xtype: 'numberfield',
            value: this.data !== null ? this.data['limit'] : null,
            fieldLabel: t('social_data.wall.feed.facebook.limit'),
            name: 'limit',
            maxValue: 500,
            minValue: 0,
            labelAlign: 'left',
            anchor: '100%',
            flex: 1
        });

        return fields;
    },

    isValid: function () {
        return this.panel.form.isValid();
    },

    getValues: function () {
        return this.panel.form.getValues();
    }
});
