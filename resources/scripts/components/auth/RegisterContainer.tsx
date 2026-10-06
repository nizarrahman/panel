import React, { useEffect, useRef, useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import { Formik, FormikHelpers } from 'formik';
import { object, ref, string } from 'yup';
import tw from 'twin.macro';
import Reaptcha from 'reaptcha';
import { useStoreState } from 'easy-peasy';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import Field from '@/components/elements/Field';
import Button from '@/components/elements/Button';
import useFlash from '@/plugins/useFlash';
import register from '@/api/auth/register';
import { httpErrorToHuman } from '@/api/http';

interface Values {
    email: string;
    username: string;
    nameFirst: string;
    nameLast: string;
    password: string;
    passwordConfirm: string;
}

const RegisterContainer = ({ history }: RouteComponentProps) => {
    const captchaRef = useRef<Reaptcha>(null);
    const [token, setToken] = useState('');

    const { clearFlashes, addFlash, clearAndAddHttpError } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState((state) => state.settings.data!.recaptcha);

    useEffect(() => {
        clearFlashes();
    }, []);

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        if (recaptchaEnabled && !token) {
            captchaRef.current!.execute().catch((error) => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
            return;
        }

        register({
            email: values.email,
            username: values.username,
            nameFirst: values.nameFirst,
            nameLast: values.nameLast,
            password: values.password,
            recaptchaData: token,
        })
            .then(() => {
                addFlash({
                    type: 'success',
                    title: 'Registration Successful',
                    message: 'Your account has been created. You can now log in.',
                });
                history.push('/auth/login');
            })
            .catch((error) => {
                console.error(error);
                setToken('');
                if (captchaRef.current) captchaRef.current.reset();
                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    };

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{ email: '', username: '', nameFirst: '', nameLast: '', password: '', passwordConfirm: '' }}
            validationSchema={object().shape({
                email: string().email('A valid email address is required.').required('Email is required.'),
                username: string()
                    .min(3, 'Username must be at least 3 characters.')
                    .max(191, 'Username must not exceed 191 characters.')
                    .required('Username is required.'),
                nameFirst: string().required('First name is required.'),
                nameLast: string().required('Last name is required.'),
                password: string()
                    .min(8, 'Password must be at least 8 characters.')
                    .required('Password is required.'),
                passwordConfirm: string()
                    .oneOf([ref('password'), null], 'Passwords do not match.')
                    .required('Please confirm your password.'),
            })}
        >
            {({ isSubmitting, setSubmitting, submitForm }) => (
                <LoginFormContainer title={'Create an Account'} css={tw`w-full flex`}>
                    <div css={tw`flex gap-4`}>
                        <div css={tw`flex-1`}>
                            <Field light type={'text'} label={'First Name'} name={'nameFirst'} disabled={isSubmitting} />
                        </div>
                        <div css={tw`flex-1`}>
                            <Field light type={'text'} label={'Last Name'} name={'nameLast'} disabled={isSubmitting} />
                        </div>
                    </div>
                    <div css={tw`mt-4`}>
                        <Field light type={'email'} label={'Email Address'} name={'email'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-4`}>
                        <Field light type={'text'} label={'Username'} name={'username'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-4`}>
                        <Field light type={'password'} label={'Password'} name={'password'} disabled={isSubmitting} />
                    </div>
                    <div css={tw`mt-4`}>
                        <Field
                            light
                            type={'password'}
                            label={'Confirm Password'}
                            name={'passwordConfirm'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting} disabled={isSubmitting}>
                            Register
                        </Button>
                    </div>
                    {recaptchaEnabled && (
                        <Reaptcha
                            ref={captchaRef}
                            size={'invisible'}
                            sitekey={siteKey || '_invalid_key'}
                            onVerify={(response) => {
                                setToken(response);
                                submitForm();
                            }}
                            onExpire={() => {
                                setSubmitting(false);
                                setToken('');
                            }}
                        />
                    )}
                    <div css={tw`mt-6 text-center`}>
                        <Link
                            to={'/auth/login'}
                            css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                        >
                            Already have an account? Login
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default RegisterContainer;
