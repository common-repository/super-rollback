document.addEventListener('DOMContentLoaded', function () {
    const { createElement: el, render, useState } = wp.element;
    const { Modal, Button, Notice, SelectControl, TextareaControl } = wp.components;
    const { __ } = wp.i18n;


    const container = document.getElementById('rollback-modal');
    let root = null;

    document.querySelectorAll('.super-rollback-link').forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const plugin = button.getAttribute('data-plugin');
            console.log('Requesting rollback data for plugin:', plugin);

            fetchRollbackData(plugin).then(data => {
                console.log('Received rollback data:', data);
                if (data.success) {
                    // showRollbackModal(data.data);
                    root = wp.element.createRoot(container);
                    root.render(wp.element.createElement(showRollbackModal, { data: data.data }));
                } else {
                    alert(data.data.message);
                }
            });
        });
    });

    function fetchRollbackData(plugin) {
        let data = new FormData();
        data.append('action', 'fetch_rollback_data');
        data.append('plugin', plugin);
        data.append('nonce', pluginRollback.nonce);

        return fetch(pluginRollback.ajaxurl, {
            method: 'POST',
            body: data
        }).then(response => response.json());
    }

    function showRollbackModal({data}) {
        const [selectedBackup, setSelectedBackup] = useState('');
        const [rollbackReason, setRollbackReason] = useState('');
        const [isProcessing, setIsProcessing] = useState(false);
        const [errorMessage, setErrorMessage] = useState('');
        const [successMessage, setSuccessMessage] = useState('');

        function handleRollback() {
            setIsProcessing(true);
            setErrorMessage('');
            setSuccessMessage('');
            // setRollbackReason('');

            const selectedBackupData = data.backups.find(backup => backup.file === selectedBackup);
            if (selectedBackupData && !selectedBackupData.available) {
                setIsProcessing(false);
                setErrorMessage(__('This version is only available with Pro.', 'super-rollback'));
                return;
            }

            const formData = new FormData();
            formData.append('action', 'initiate_rollback');
            formData.append('plugin', data.plugin);
            formData.append('backup', selectedBackup);
            formData.append('reason', rollbackReason);
            formData.append('nonce', pluginRollback.nonce);

            fetch(pluginRollback.ajaxurl, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(result => {
                setIsProcessing(false);
                console.log(result.success);
                if (result.success) {
                    setSuccessMessage(result.data.message);
                } else {
                    setErrorMessage(result.data.message || __('Rollback failed.', 'super-rollback'));
                }
            });
        }

        function closeModal() {
            if(errorMessage || successMessage) {
                window.location.reload();
                return;
            }
            root.unmount();
        }

        const currentVersion = data.current_version;
        // const currentVersion = '1.0.0';
        return el(Modal, {
            title: __('Choose a new version', 'super-rollback'),
            className: 'rollback-modal',
            onRequestClose: () => closeModal(),
            shouldCloseOnOverlayClick: true
        },
            el('div', null,
                // el('h2', null, data.plugin_name),
                errorMessage && el(Notice, { status: 'error', isDismissible: false }, errorMessage),
                successMessage && el(Notice, { status: 'success', isDismissible: false }, el('strong', null, 
                __('Super! ', 'super-rollback')
            ), successMessage),
                el('p', { style: { marginTop: '0', marginBottom: '20px' } }, __('Choose which version you would like to rollback to from the versions listed below.', 'super-rollback')),
                el('table', { className: 'widefat'},
                    el('tbody', null, 
                        el('tr', null, 
                        el('td', { className: 'row-title' }, __('Plugin:', 'super-rollback')), 
                            el('td', null, data.plugin_name)
                        ),
                        el('tr', { className: 'alternate' }, 
                            el('td', { className: 'row-title' }, 
                            __('Current Version:', 'super-rollback')), 
                            el('td', null, currentVersion)
                        ),
                        el('tr', null, 
                            el('td', { className: 'row-title' }, __('New Version:', 'super-rollback')), 
                            el('td', { style: {
                                        paddingBottom: '0',
                                        paddingLeft: '2px',
                                    }
                                }, 
                                el(SelectControl, {
                                    // label: __('Select Version', 'super-rollback'),
                                    value: selectedBackup,
                                    required: true,
                                    disabled: isProcessing || successMessage,
                                    options: [
                                        { label: 'Choose version', value: '' },
                                        ...data.backups.map(backup => ({
                                            label: backup.version + (backup.current ? ' (Current Version)' : '') + (backup.available ? '' : ' (Available with Pro)'),
                                            value: backup.file,
                                            disabled: backup.current || !backup.available
                                        }))
                                    ],
                                    onChange: setSelectedBackup
                                })
                            )
                        ),
                        el('tr', { className: 'alternate' }, 
                            el('td', { className: 'row-title' }, __('Reason:', 'super-rollback')), 
                            el('td', {
                                    style: {
                                        paddingBottom: '0',
                                        paddingLeft: '2px',
                                    }
                                }, 
                                el(TextareaControl, {
                                    onChange: setRollbackReason,
                                    value: rollbackReason,
                                    disabled: isProcessing || successMessage,
                                    placeholder: __('I.e. Compatibility issue with [plugin]', 'super-rollback'),
                                    rows: 2
                                })
                            ),
                        )
                    )
                ),
                el(Notice, { status: 'warning', isDismissible: false },
                    el('strong', null, 
                        __('Notice: ', 'super-rollback')
                    ),
                    __('We highly advise creating a full backup of your WordPress files and database prior to proceeding with this rollback. We are not responsible for any issues or errors resulting from using this plugin.', 'super-rollback')
                ),
                el('div', { style: { display: 'flex', justifyContent: 'flex-end', marginTop: '30px' } },
                    !successMessage && el(Button, {
                        isSecondary: true,
                        onClick: () => root.unmount()
                    }, __('Cancel', 'super-rollback')),
                    !successMessage && el(Button, {
                        isPrimary: true,
                        isBusy: isProcessing,
                        onClick: handleRollback,
                        // disabled: !selectedBackup || isProcessing,
                        style: { marginLeft: '20px' },
                    }, __('Confirm Rollback', 'super-rollback')),
                    successMessage && el(Button, {
                        isPrimary: true,
                        isBusy: isProcessing,
                        onClick: closeModal,
                    }, __('Back to Plugins', 'super-rollback')),
                )
            )
        );
    }
});