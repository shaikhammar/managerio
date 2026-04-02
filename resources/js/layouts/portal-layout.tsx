import type { ReactNode } from 'react';

type Props = {
    businessName: string;
    logoPath?: string | null;
    children: ReactNode;
};

export default function PortalLayout({ businessName, logoPath, children }: Props) {
    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950">
            <header className="border-b bg-white dark:bg-gray-900 dark:border-gray-800">
                <div className="max-w-4xl mx-auto px-4 py-4 flex items-center gap-3">
                    {logoPath && (
                        <img src={`/storage/${logoPath}`} alt={businessName} className="h-8 w-auto object-contain" />
                    )}
                    <span className="font-semibold text-gray-900 dark:text-white">{businessName}</span>
                </div>
            </header>
            <main className="max-w-4xl mx-auto px-4 py-8">{children}</main>
        </div>
    );
}
