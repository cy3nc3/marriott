export type DeviceLoginToken = {
    device_id: string;
    selector: string;
    token: string;
    expires_at: string;
};

export type StoredLoginAccount = {
    email: string;
    remember: boolean;
    lastUsedAt: string;
    deviceLogin: DeviceLoginToken | null;
};

type SavedAccountLoginFlash =
    | {
          action: 'store';
          account: {
              email: string;
              remember: boolean;
              last_used_at: string;
              device_login: DeviceLoginToken;
          };
      }
    | {
          action: 'forget';
          email: string;
          device_id: string;
      };

const LOGIN_ACCOUNTS_STORAGE_KEY = 'marriottconnect_login_accounts';
const LOGIN_DEVICE_ID_STORAGE_KEY = 'marriottconnect_login_device_id';
const MAX_STORED_ACCOUNTS = 6;

export const getSavedAccountDeviceId = (): string => {
    if (typeof window === 'undefined') {
        return '';
    }

    const existing = window.localStorage.getItem(LOGIN_DEVICE_ID_STORAGE_KEY);
    if (typeof existing === 'string' && existing.trim() !== '') {
        return existing;
    }

    const generated = window.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`;
    window.localStorage.setItem(LOGIN_DEVICE_ID_STORAGE_KEY, generated);

    return generated;
};

const normalizeEmail = (email: string): string => email.trim().toLowerCase();

const isValidDeviceLoginToken = (value: unknown): value is DeviceLoginToken => {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const candidate = value as Partial<DeviceLoginToken>;

    return (
        typeof candidate.device_id === 'string' &&
        candidate.device_id !== '' &&
        typeof candidate.selector === 'string' &&
        candidate.selector !== '' &&
        typeof candidate.token === 'string' &&
        candidate.token !== '' &&
        typeof candidate.expires_at === 'string' &&
        candidate.expires_at !== ''
    );
};

export const readStoredLoginAccounts = (): StoredLoginAccount[] => {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const payload = window.localStorage.getItem(LOGIN_ACCOUNTS_STORAGE_KEY);
        if (!payload) {
            return [];
        }

        const parsed = JSON.parse(payload);
        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map((item): StoredLoginAccount | null => {
                if (typeof item !== 'object' || item === null) {
                    return null;
                }

                const account = item as {
                    email?: unknown;
                    remember?: unknown;
                    lastUsedAt?: unknown;
                    deviceLogin?: unknown;
                };

                if (
                    typeof account.email !== 'string' ||
                    account.email.trim() === '' ||
                    typeof account.remember !== 'boolean' ||
                    typeof account.lastUsedAt !== 'string'
                ) {
                    return null;
                }

                return {
                    email: normalizeEmail(account.email),
                    remember: account.remember,
                    lastUsedAt: account.lastUsedAt,
                    deviceLogin: isValidDeviceLoginToken(account.deviceLogin)
                        ? account.deviceLogin
                        : null,
                };
            })
            .filter((item): item is StoredLoginAccount => item !== null)
            .sort(
                (left, right) =>
                    new Date(right.lastUsedAt).getTime() -
                    new Date(left.lastUsedAt).getTime(),
            );
    } catch {
        return [];
    }
};

export const writeStoredLoginAccounts = (
    accounts: StoredLoginAccount[],
): void => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(
        LOGIN_ACCOUNTS_STORAGE_KEY,
        JSON.stringify(accounts),
    );
};

export const upsertStoredLoginAccount = (
    accounts: StoredLoginAccount[],
    account: {
        email: string;
        remember: boolean;
        lastUsedAt?: string;
        deviceLogin?: DeviceLoginToken | null;
    },
): StoredLoginAccount[] => {
    const email = normalizeEmail(account.email);
    if (email === '') {
        return accounts;
    }

    const previous = accounts.find(
        (storedAccount) => normalizeEmail(storedAccount.email) === email,
    );

    const nextAccounts: StoredLoginAccount[] = [
        {
            email,
            remember: account.remember,
            lastUsedAt: account.lastUsedAt ?? new Date().toISOString(),
            deviceLogin:
                account.deviceLogin !== undefined
                    ? account.deviceLogin
                    : previous?.deviceLogin ?? null,
        },
        ...accounts.filter(
            (storedAccount) => normalizeEmail(storedAccount.email) !== email,
        ),
    ].slice(0, MAX_STORED_ACCOUNTS);

    return nextAccounts;
};

export const clearStoredLoginDeviceToken = (
    accounts: StoredLoginAccount[],
    email: string,
    deviceId?: string,
): StoredLoginAccount[] => {
    const normalizedEmail = normalizeEmail(email);

    return accounts.map((account) => {
        if (normalizeEmail(account.email) !== normalizedEmail) {
            return account;
        }

        if (
            deviceId &&
            account.deviceLogin &&
            account.deviceLogin.device_id !== deviceId
        ) {
            return account;
        }

        return {
            ...account,
            remember: false,
            deviceLogin: null,
        };
    });
};

export const isDeviceLoginTokenExpired = (token: DeviceLoginToken): boolean => {
    const expiresAt = new Date(token.expires_at);

    return Number.isNaN(expiresAt.getTime()) || expiresAt.getTime() <= Date.now();
};

export const applySavedAccountLoginFlash = (
    payload: SavedAccountLoginFlash,
): StoredLoginAccount[] => {
    const accounts = readStoredLoginAccounts();

    if (payload.action === 'store') {
        const nextAccounts = upsertStoredLoginAccount(accounts, {
            email: payload.account.email,
            remember: payload.account.remember,
            lastUsedAt: payload.account.last_used_at,
            deviceLogin: payload.account.device_login,
        });

        writeStoredLoginAccounts(nextAccounts);

        return nextAccounts;
    }

    const nextAccounts = clearStoredLoginDeviceToken(
        accounts,
        payload.email,
        payload.device_id,
    );
    writeStoredLoginAccounts(nextAccounts);

    return nextAccounts;
};
