import React from 'react';

interface SkeletonLoaderProps {
    type: 'card' | 'table' | 'form' | 'text' | 'avatar';
    count?: number;
}

function SkeletonItem({ type }: { type: SkeletonLoaderProps['type'] }) {
    const baseClasses = 'animate-pulse bg-gray-200 dark:bg-gray-700 rounded';

    switch (type) {
        case 'card':
            return (
                <div className="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                    <div className={`${baseClasses} h-40 w-full rounded-lg`} />
                    <div className={`${baseClasses} h-4 w-3/4`} />
                    <div className={`${baseClasses} h-4 w-1/2`} />
                    <div className={`${baseClasses} h-8 w-1/3 mt-2`} />
                </div>
            );

        case 'table':
            return (
                <div className="space-y-2">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <div key={i} className="flex gap-4 py-3">
                            <div className={`${baseClasses} h-4 w-1/4`} />
                            <div className={`${baseClasses} h-4 w-1/3`} />
                            <div className={`${baseClasses} h-4 w-1/6`} />
                            <div className={`${baseClasses} h-4 w-1/4`} />
                        </div>
                    ))}
                </div>
            );

        case 'form':
            return (
                <div className="space-y-4">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <div key={i} className="space-y-2">
                            <div className={`${baseClasses} h-4 w-1/4`} />
                            <div className={`${baseClasses} h-10 w-full`} />
                        </div>
                    ))}
                    <div className={`${baseClasses} h-10 w-32 mt-4`} />
                </div>
            );

        case 'text':
            return (
                <div className="space-y-2">
                    <div className={`${baseClasses} h-4 w-full`} />
                    <div className={`${baseClasses} h-4 w-5/6`} />
                    <div className={`${baseClasses} h-4 w-4/6`} />
                </div>
            );

        case 'avatar':
            return (
                <div className="flex items-center gap-3">
                    <div className={`${baseClasses} h-12 w-12 rounded-full`} />
                    <div className="space-y-2 flex-1">
                        <div className={`${baseClasses} h-4 w-1/3`} />
                        <div className={`${baseClasses} h-3 w-1/4`} />
                    </div>
                </div>
            );

        default:
            return <div className={`${baseClasses} h-4 w-full`} />;
    }
}

export default function SkeletonLoader({ type, count = 1 }: SkeletonLoaderProps) {
    return (
        <div className="space-y-4">
            {Array.from({ length: count }).map((_, index) => (
                <SkeletonItem key={index} type={type} />
            ))}
        </div>
    );
}
