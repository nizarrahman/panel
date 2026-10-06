import http from '@/api/http';

export interface RegisterData {
    email: string;
    username: string;
    nameFirst: string;
    nameLast: string;
    password: string;
    recaptchaData?: string | null;
}

export default ({ email, username, nameFirst, nameLast, password, recaptchaData }: RegisterData): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.get('/sanctum/csrf-cookie')
            .then(() =>
                http.post('/auth/register', {
                    email,
                    username,
                    name_first: nameFirst,
                    name_last: nameLast,
                    password,
                    'g-recaptcha-response': recaptchaData,
                })
            )
            .then(() => resolve())
            .catch(reject);
    });
};
