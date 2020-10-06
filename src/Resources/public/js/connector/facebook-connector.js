pimcore.registerNS('SocialData.Connector.Facebook');
SocialData.Connector.Facebook = Class.create(SocialData.Connector.AbstractConnector, {

    hasCustomConfiguration: function () {
        return true;
    },

    afterSaveCustomConfiguration: function () {

        var fieldset = this.customConfigurationPanel.up('fieldset').previousSibling();

        this.changeState(fieldset, 'connection');
    },

    afterChangeState: function (stateType, active) {
        if (stateType === 'connection' && active === true) {
            this.refreshCustomConfigurationPanel();
        }
    },

    beforeDisableFieldState: function (stateType, toDisableState) {

        if (stateType === 'connection' && toDisableState === false) {
            return !(
                this.customConfiguration.hasOwnProperty('appId') &&
                this.customConfiguration.hasOwnProperty('appSecret')
            );
        }

        return toDisableState;
    },

    connectHandler: function (stateType, mainBtn) {

        var stateData = this.states[stateType],
            flag = this.data[stateData.identifier] === true ? 'deactivate' : 'activate';

        // just go by default
        if (flag === 'deactivate') {
            this.stateHandler(stateType, mainBtn);
            return;
        }

        mainBtn.setDisabled(true);

        var win = new Ext.Window({
            width: 400,
            modal: true,
            bodyStyle: 'padding:10px',
            title: t('social_data.connector.facebook.connect_service'),
            html: t('social_data.connector.facebook.connect_service_note'),
            listeners: {
                beforeclose: function () {
                    mainBtn.setDisabled(false);
                }
            },
            buttons: [
                {
                    text: t('social_data.connector.facebook.connect'),
                    iconCls: 'pimcore_icon_open_window',
                    handler: this.handleConnectWindow.bind(this, mainBtn)
                }
            ]
        });

        win.show();
    },

    handleConnectWindow: function (mainBtn, btn) {

        var win = btn.up('window'),
            connectWindow;

        btn.setDisabled(true);
        win.setLoading(true);

        connectWindow = new SocialData.Component.ConnectWindow(
            '/admin/social-data/connector/facebook/connect',
            // success
            function (stateData) {
                win.setLoading(false);
                win.close();
                this.stateHandler('connection', mainBtn);
            }.bind(this),
            // error
            function (stateData) {
                win.setLoading(false);
                btn.setDisabled(false);
                Ext.MessageBox.alert(t('error') + ' ' + stateData.identifier, stateData.description + ' (' + stateData.reason + ')');
            },
            // closed
            function () {
                btn.setDisabled(false);
                win.setLoading(false);
            }
        );

        connectWindow.open();
    },

    getCustomConfigurationFields: function () {

        var data = this.customConfiguration;

        return [
            {
                xtype: 'textfield',
                fieldLabel: 'Token Expiring Date',
                disabled: true,
                hidden: !data.hasOwnProperty('accessToken') || data.accessToken === null || data.accessToken === '',
                value: data.hasOwnProperty('accessTokenExpiresAt') ? data.accessTokenExpiresAt === null ? 'never' : data.accessTokenExpiresAt : '--'
            },
            {
                trackResetOnLoad: true,
                xtype: 'textfield',
                name: 'appId',
                fieldLabel: 'App ID',
                allowBlank: false,
                value: data.hasOwnProperty('appId') ? data.appId : null
            },
            {
                trackResetOnLoad: true,
                xtype: 'textfield',
                name: 'appSecret',
                fieldLabel: 'App Secret',
                allowBlank: false,
                value: data.hasOwnProperty('appSecret') ? data.appSecret : null
            },
            {
                xtype: 'button',
                text: 'Debug Token',
                iconCls: 'pimcore_icon_open_window',
                hidden: !data.hasOwnProperty('accessToken') || data.accessToken === null || data.accessToken === '',
                handler: function () {
                    Ext.Ajax.request({
                        url: '/admin/social-data/connector/facebook/debug-token',
                        method: 'GET',
                        success: function (response) {

                            var debugWindow,
                                gridData = [],
                                res = Ext.decode(response.responseText);

                            if (res.success !== true) {
                                Ext.MessageBox.alert(t('error'), res.message);
                                return;
                            }

                            Ext.Object.each(res.data, function (g, i) {
                                gridData.push({label: g, value: Ext.encode(i)});
                            })

                            debugWindow = new Ext.Window({
                                width: 700,
                                height: 500,
                                modal: true,
                                title: t('Token Debug'),
                                layout: 'fit',
                                items: [
                                    new Ext.grid.GridPanel({
                                        flex: 1,
                                        store: new Ext.data.Store({
                                            fields: ['label', 'value'],
                                            data: gridData
                                        }),
                                        border: true,
                                        columnLines: true,
                                        stripeRows: true,
                                        title: false,
                                        columns: [
                                            {
                                                text: t('label'),
                                                sortable: false,
                                                dataIndex: 'label',
                                                hidden: false,
                                                flex: 1,
                                            },
                                            {
                                                cellWrap: true,
                                                text: t('value'),
                                                sortable: false,
                                                dataIndex: 'value',
                                                hidden: false,
                                                flex: 2
                                            }
                                        ]
                                    })
                                ]
                            });

                            debugWindow.show();

                        }.bind(this)
                    });
                }.bind(this)
            },
        ];
    }
});