import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M4,38 L4,4 L20,20 L36,4 L36,38 L30,38 L30,16 L20,26 L10,16 L10,38 Z" />
        </svg>
    );
}
