(() => {
  document.querySelectorAll('[data-mm-mail]').forEach((link) => {
    const local = String(link.dataset.mmLocal || '').split('').reverse().join('');
    const domain = String(link.dataset.mmDomain || '').split('').reverse().join('');
    if (!local || !domain) return;
    const address = local + '@' + domain;
    link.href = 'mailto:' + address;
    if (link.dataset.mmReveal === '1') {
      link.textContent = address;
    }
  });


  const activationForm = document.querySelector('[data-elite-activation-form]');
  if (activationForm) {
    const password = activationForm.querySelector('[data-elite-password]');
    const repeat = activationForm.querySelector('[data-elite-password-repeat]');
    const error = activationForm.querySelector('[data-elite-password-error]');

    const showError = (message) => {
      if (!error) return;
      error.textContent = message;
      error.hidden = false;
    };

    activationForm.addEventListener('submit', (event) => {
      if (!password || !repeat) return;
      if (password.value.length < 12) {
        event.preventDefault();
        showError('Das Passwort muss mindestens 12 Zeichen lang sein.');
        password.focus();
        return;
      }
      if (password.value !== repeat.value) {
        event.preventDefault();
        showError('Die Passwörter stimmen nicht überein.');
        repeat.focus();
        return;
      }
      if (error) error.hidden = true;
    });

    password?.addEventListener('input', () => {
      if (password.value.length > 0 && password.value.length < 12) {
        showError('Noch ' + (12 - password.value.length) + ' Zeichen bis zur Mindestlänge.');
      } else if (error) {
        error.hidden = true;
      }
    });
  }


  const atlasForm = document.querySelector('[data-atlas-address-form]');
  if (atlasForm) {
    const country = atlasForm.querySelector('[data-atlas-country]');
    const subdivision = atlasForm.querySelector('[data-atlas-subdivision]');
    const postal = atlasForm.querySelector('[data-atlas-postal]');
    const locality = atlasForm.querySelector('[data-atlas-locality]');
    const localityManual = atlasForm.querySelector('[data-atlas-locality-manual]');
    const street = atlasForm.querySelector('[data-atlas-street]');
    const streetOptions = atlasForm.querySelector('[data-atlas-street-options]');
    const streetStatus = atlasForm.querySelector('[data-atlas-street-status]');
    const postalStatus = atlasForm.querySelector('[data-atlas-postal-status]');
    const adminId = atlasForm.querySelector('[data-atlas-admin-id]');
    const adminName = atlasForm.querySelector('[data-atlas-admin-name]');
    const postalId = atlasForm.querySelector('[data-atlas-postal-id]');
    const localityId = atlasForm.querySelector('[data-atlas-locality-id]');
    const localityName = atlasForm.querySelector('[data-atlas-locality-name]');
    const streetAtlasId = atlasForm.querySelector('[data-atlas-street-id]');

    const endpoint = '/backoffice/atlas-reference.php';

    const atlasGet = async (params) => {
      const url = endpoint + '?' + new URLSearchParams(params).toString();
      const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {'Accept': 'application/json'}
      });
      const payload = await response.json();
      if (!response.ok || payload?.success !== true || !Array.isArray(payload?.data) && typeof payload?.data !== 'object') {
        throw new Error(payload?.error?.message || 'ATLAS request failed');
      }
      return payload.data;
    };

    const setOptions = (select, items, valueKey, labelFn, selectedValue, emptyLabel) => {
      if (!select) return;
      select.replaceChildren();
      const empty = document.createElement('option');
      empty.value = '';
      empty.textContent = emptyLabel;
      select.appendChild(empty);

      items.forEach((item) => {
        const option = document.createElement('option');
        option.value = String(item[valueKey] ?? '');
        option.textContent = labelFn(item);
        if (selectedValue && option.value === selectedValue) option.selected = true;
        select.appendChild(option);
      });
    };

    const loadCountries = async () => {
      if (!country) return;
      const current = String(country.dataset.currentCountry || country.value || '').toUpperCase();
      try {
        const items = await atlasGet({action:'countries'});
        setOptions(
          country,
          Array.isArray(items) ? items : [],
          'iso_alpha2',
          (item) => String(item.name || item.iso_alpha2 || ''),
          current,
          'Land wählen'
        );
        if (current) country.value = current;
        await loadSubdivisions();
        if (postal?.value) await resolvePostal();
      } catch (_) {
        if (postalStatus) postalStatus.textContent = 'ATLAS derzeit nicht erreichbar – bestehende Werte bleiben erhalten.';
      }
    };

    const loadSubdivisions = async () => {
      if (!country || !subdivision) return;
      const code = String(country.value || '').toUpperCase();
      const current = String(adminId?.value || subdivision.dataset.currentAdmin || '');
      if (!code) {
        setOptions(subdivision, [], 'atlas_id', () => '', '', 'Bitte zuerst Land wählen');
        return;
      }
      try {
        const items = await atlasGet({action:'subdivisions', country_code:code});
        setOptions(
          subdivision,
          Array.isArray(items) ? items : [],
          'atlas_id',
          (item) => String(item.name || item.local_name || item.code || ''),
          current,
          'Region optional'
        );
        if (current) subdivision.value = current;
      } catch (_) {
        setOptions(subdivision, [], 'atlas_id', () => '', '', 'Region nicht verfügbar');
      }
    };

    const loadLocalities = async () => {
      if (!country || !postal || !locality) return;
      const code = String(country.value || '').toUpperCase();
      const postalCode = String(postal.value || '').trim();
      const currentId = String(localityId?.value || locality.dataset.currentLocality || '');
      const currentName = String(localityName?.value || locality.value || '');

      if (!code || !postalCode) return;

      try {
        const items = await atlasGet({
          action:'localities',
          country_code:code,
          postal_code:postalCode,
          limit:'50'
        });
        const list = Array.isArray(items) ? items : [];
        locality.replaceChildren();

        if (!list.length) {
          const option = document.createElement('option');
          option.value = currentName;
          option.textContent = currentName || 'Ort nicht in ATLAS – bestehender Freitext bleibt möglich';
          option.selected = true;
          locality.appendChild(option);
          if (localityId) localityId.value = '';
          return;
        }

        list.forEach((item) => {
          const option = document.createElement('option');
          option.value = String(item.name || '');
          option.dataset.atlasId = String(item.atlas_id || '');
          option.dataset.postalId = String(item.postal_area_id || '');
          option.dataset.adminId = String(item.administrative_unit_id || '');
          option.textContent = String(item.name || '');
          if ((currentId && option.dataset.atlasId === currentId) || (!currentId && currentName && option.value === currentName)) {
            option.selected = true;
          }
          locality.appendChild(option);
        });

        if (locality.selectedIndex < 0 && locality.options.length) locality.selectedIndex = 0;
        syncLocality();
      } catch (_) {
        if (postalStatus) postalStatus.textContent = 'PLZ gefunden, Ortsliste momentan nicht verfügbar.';
      }
    };

    const resolvePostal = async () => {
      if (!country || !postal) return;
      const code = String(country.value || '').toUpperCase();
      const postalCode = String(postal.value || '').trim();

      if (!code || !postalCode) {
        if (postalId) postalId.value = '';
        if (postalStatus) postalStatus.textContent = 'Land und PLZ eingeben.';
        return;
      }

      if (postalStatus) postalStatus.textContent = 'PLZ wird über ATLAS geprüft …';

      try {
        const item = await atlasGet({
          action:'postal_area',
          country_code:code,
          postal_code:postalCode
        });
        if (postalId) postalId.value = String(item?.atlas_id || '');
        if (postalStatus) {
          postalStatus.textContent = item?.atlas_id
            ? 'PLZ durch ATLAS bestätigt.'
            : 'PLZ bleibt als Freitext gespeichert.';
        }
        await loadLocalities();
      } catch (_) {
        if (postalId) postalId.value = '';
        if (localityId) localityId.value = '';
        if (postalStatus) postalStatus.textContent = 'Keine ATLAS-Referenz gefunden – Freitext bleibt zulässig.';
      }
    };


    const loadStreets = async () => {
      if (!country || !postal || !street || !streetOptions || !localityId) return;

      const code = String(country.value || '').toUpperCase();
      const postalCode = String(postal.value || '').trim();
      const locId = String(localityId.value || '').trim();
      const query = String(street.value || '').trim();

      if (!code || !postalCode || !locId || query.length < 2) {
        streetOptions.replaceChildren();
        if (streetStatus) {
          streetStatus.textContent = locId
            ? 'ATLAS-Suche ab 2 Zeichen; Freitext bleibt möglich.'
            : 'Für ATLAS-Straßensuche zuerst einen ATLAS-Ort wählen.';
        }
        return;
      }

      if (streetStatus) streetStatus.textContent = 'Straßen werden über ATLAS gesucht …';

      try {
        const items = await atlasGet({
          action:'streets',
          country_code:code,
          postal_code:postalCode,
          locality_id:locId,
          q:query,
          limit:'20'
        });

        const list = Array.isArray(items) ? items : [];
        streetOptions.replaceChildren();

        list.forEach((item) => {
          const option = document.createElement('option');
          option.value = String(item.name || '');
          option.dataset.atlasId = String(item.atlas_id || '');
          option.dataset.localityId = String(item.locality_id || '');
          option.dataset.postalId = String(item.postal_area_id || '');
          streetOptions.appendChild(option);
        });

        if (streetStatus) {
          streetStatus.textContent = list.length
            ? list.length + ' ATLAS-Treffer.'
            : 'Keine ATLAS-Straße gefunden – Freitext bleibt möglich.';
        }
      } catch (_) {
        streetOptions.replaceChildren();
        if (streetStatus) streetStatus.textContent = 'Straßenreferenz nicht verfügbar – Freitext bleibt möglich.';
      }
    };

    const syncStreet = () => {
      if (!street || !streetOptions || !streetAtlasId) return;
      const value = String(street.value || '').trim();
      const option = Array.from(streetOptions.options).find((candidate) => candidate.value === value);
      streetAtlasId.value = option?.dataset?.atlasId || '';
      if (option?.dataset?.postalId && postalId) postalId.value = option.dataset.postalId;
      if (streetStatus && value !== '') {
        streetStatus.textContent = option?.dataset?.atlasId
          ? 'Straße durch ATLAS referenziert.'
          : 'Straße wird als Freitext gespeichert.';
      }
    };


    const syncSubdivision = () => {
      if (!subdivision) return;
      const option = subdivision.options[subdivision.selectedIndex];
      if (adminId) adminId.value = option?.value || '';
      if (adminName) adminName.value = option && option.value ? option.textContent.trim() : '';
    };

    const syncLocality = () => {
      if (!locality) return;
      const option = locality.options[locality.selectedIndex];
      if (localityId) localityId.value = option?.dataset?.atlasId || '';
      if (localityName) localityName.value = option?.value || '';
      if (postalId && option?.dataset?.postalId) postalId.value = option.dataset.postalId;
      if (adminId && !adminId.value && option?.dataset?.adminId) adminId.value = option.dataset.adminId;
    };

    let postalTimer = 0;
    let streetTimer = 0;
    country?.addEventListener('change', async () => {
      if (adminId) adminId.value = '';
      if (adminName) adminName.value = '';
      if (postalId) postalId.value = '';
      if (localityId) localityId.value = '';
      if (streetAtlasId) streetAtlasId.value = '';
      if (streetOptions) streetOptions.replaceChildren();
      await loadSubdivisions();
      if (postal?.value) await resolvePostal();
    });
    subdivision?.addEventListener('change', syncSubdivision);
    locality?.addEventListener('change', () => {
      if (localityManual) localityManual.value = '';
      if (streetAtlasId) streetAtlasId.value = '';
      if (streetOptions) streetOptions.replaceChildren();
      syncLocality();
    });
    localityManual?.addEventListener('input', () => {
      if (localityId) localityId.value = '';
      if (localityName) localityName.value = localityManual.value.trim();
      if (streetAtlasId) streetAtlasId.value = '';
      if (streetOptions) streetOptions.replaceChildren();
    });
    street?.addEventListener('input', () => {
      if (streetAtlasId) streetAtlasId.value = '';
      window.clearTimeout(streetTimer);
      streetTimer = window.setTimeout(loadStreets, 300);
    });
    street?.addEventListener('change', syncStreet);
    street?.addEventListener('blur', syncStreet);
    postal?.addEventListener('input', () => {
      if (postalId) postalId.value = '';
      if (localityId) localityId.value = '';
      if (streetAtlasId) streetAtlasId.value = '';
      if (streetOptions) streetOptions.replaceChildren();
      window.clearTimeout(postalTimer);
      postalTimer = window.setTimeout(resolvePostal, 450);
    });
    postal?.addEventListener('blur', resolvePostal);

    loadCountries();
  }
})();
