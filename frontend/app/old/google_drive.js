

  // Authorization scopes required by the API; multiple scopes can be
  // included, separated by spaces.
  const SCOPES = 'https://www.googleapis.com/auth/drive.metadata.readonly';

  // TODO(developer): Set to client ID and API key from the Developer Console
  const CLIENT_ID = '855681743529-3vnutvph4qp5c4hocflqva6064m7e779.apps.googleusercontent.com';
  const API_KEY = 'AIzaSyBDbvDtBT03FuJyhqwm0eoYlPhB3jqKHtI';

  // TODO(developer): Replace with your own project number from console.developers.google.com.
  const APP_ID = '855681743529';

  let tokenClient;
  let accessToken = null;
  let gisInited = false;


  /**
   * Callback after api.js is loaded.
   */
  function gapiLoaded() {
    gapi.load('picker');
  }

  /**
   * Callback after Google Identity Services are loaded.
   */
  function gisLoaded() {
    tokenClient = google.accounts.oauth2.initTokenClient({
      client_id: CLIENT_ID,
      scope: SCOPES,
      callback: '', // defined later
    });
    gisInited = true;
  }

  /**
   *  Sign in the user upon button click.
   */
  function googleDriveLoad() {
    tokenClient.callback = async (response) => {
      if (response.error !== undefined) {
        throw (response);
      }
      accessToken = response.access_token;
      await createPicker();
    };

    if (accessToken === null) {
      // Prompt the user to select a Google Account and ask for consent to share their data
      // when establishing a new session.
      tokenClient.requestAccessToken({prompt: 'consent'});
    } else {
      // Skip display of account chooser and consent dialog for an existing session.
      tokenClient.requestAccessToken({prompt: ''});
    }
  }

  /**
   *  Sign out the user upon button click.
   */
  function handleSignOutClick() {
    if (accessToken) {
      accessToken = null;
      google.accounts.oauth2.revoke(accessToken);
    }
  }

  /**
   *  Create and render a Picker object for searching images.
   */
  function createPicker() {
    const view = new google.picker.View(google.picker.ViewId.DOCS);
    //view.setMimeTypes('image/png,image/jpeg,image/jpg');
    const picker = new google.picker.PickerBuilder()
        //.enableFeature(google.picker.Feature.NAV_HIDDEN)
        .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
        .setDeveloperKey(API_KEY)
        .setAppId(APP_ID)
        .setOAuthToken(accessToken)
        .addView(view)
        .addView(new google.picker.DocsUploadView())
        .setCallback(pickerCallback)
        .build();
    picker.setVisible(true);
  }

  /**
   * Displays the file details of the user's selection.
   * @param {object} data - Containers the user selection from the picker
   */
  function pickerCallback(data) {
    if (data.action === google.picker.Action.PICKED) {
      console.log(JSON.stringify(data, null, 2));
      window.onDriveFilePick(JSON.stringify(data));
    }
  }