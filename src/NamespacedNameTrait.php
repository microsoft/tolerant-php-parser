<?php
/*---------------------------------------------------------------------------------------------
 *  Copyright (c) Microsoft Corporation. All rights reserved.
 *  Licensed under the MIT License. See License.txt in the project root for license information.
 *--------------------------------------------------------------------------------------------*/

namespace Microsoft\PhpParser;

trait NamespacedNameTrait {
    public abstract function getNamespaceDefinition();
    public abstract function getFileContents() : string;
    public abstract function getNameParts() : array;

    /**
     * Gets resolved name from current namespace. Note that this is not necessarily the *actual* name
     * that is resolved during compilation or at runtime. For that, see QualifiedName::getResolvedName().
     *
     * @return ResolvedName
     */
    public function getNamespacedName() : ResolvedName {
        $namespaceDefinition = $this->getNamespaceDefinition();
        $content = $this->getFileContents();
        if ($namespaceDefinition === null) {
            // global namespace -> strip namespace\ prefix
            return ResolvedName::buildName($this->getNameParts(), $content);
        }

        if ($namespaceDefinition->name !== null) {
            $resolvedName = ResolvedName::buildName($namespaceDefinition->name->nameParts, $content);
        } else {
            $resolvedName = ResolvedName::buildName([], $content);
        }
        $resolvedName->addNameParts($this->getNameParts(), $content);
        return $resolvedName;
    }
}