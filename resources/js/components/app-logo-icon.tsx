import type { SVGAttributes } from 'react';

/** Marca de ATTENT: una "A" abierta con un punto de atención en su interior. */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M8 33 L20 7 L32 33" fill="none" stroke="currentColor" strokeWidth="5" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="20" cy="26" r="3.4" fill="currentColor" />
        </svg>
    );
}
